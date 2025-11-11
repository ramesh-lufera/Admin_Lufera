<?php 
ob_start();
include './partials/layouts/layoutTop.php'; 

ini_set('display_errors', 0);
error_reporting(E_ALL);

function getItems($conn, $table, $nameField) {
    $items = [];
    $sql = "SELECT id, $nameField AS name FROM `$table` ORDER BY id ASC";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
    }
    return $items;
}

$packages = getItems($conn, 'package', 'package_name');
$products = getItems($conn, 'products', 'name');
$addons   = getItems($conn, 'add-on-service', 'name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ob_clean();

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $tax_name = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? 'All');
        $rate = floatval($_POST['rate'] ?? 0);
        $type = trim($_POST['type'] ?? 'exclusive');
        $set_default = isset($_POST['set_default']) ? 1 : 0;

        $apply_to = $_POST['apply_to'] ?? [];
        $package_items = $_POST['package_items'] ?? [];
        $product_items = $_POST['product_items'] ?? [];
        $addon_items = $_POST['addon_items'] ?? [];

        $services = [
            'apply_to' => $apply_to,
            'packages' => $package_items,
            'products' => $product_items,
            'addons'   => $addon_items
        ];
        $services_json = json_encode($services);

        if (empty($tax_name) || empty($country)) {
            echo json_encode(['status' => 'error', 'message' => 'Tax name and country are required.']);
            exit;
        }

        if ($action === 'create') {
            $sql = "INSERT INTO `taxes` (`tax_name`, `country/region`, `rate`, `type`, `services`, `set_default`)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdssi', $tax_name, $country, $rate, $type, $services_json, $set_default);
        } else {
            $id = intval($_POST['id']);
            $sql = "UPDATE `taxes` SET `tax_name`=?, `country/region`=?, `rate`=?, `type`=?, `services`=?, `set_default`=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdssii', $tax_name, $country, $rate, $type, $services_json, $set_default, $id);
        }

        // if ($stmt->execute()) {
        //     $msg = ($action === 'create') ? 'Tax Added' : 'Tax Updated';
        //     echo json_encode(['status' => 'success', 'message' => $msg]);
        // } else {
        if ($stmt->execute()) {
            // Get current tax ID (insert or update)
            $tax_id = ($action === 'create') ? $stmt->insert_id : $id;

            // If applying to products, update gst column for selected products
            if (in_array('products', $apply_to) && !empty($product_items)) {
                // First clear gst for products not selected
                $conn->query("UPDATE `products` SET `gst` = NULL WHERE `gst` = $tax_id");

                // Set gst for selected products
                foreach ($product_items as $pid) {
                    $pid = intval($pid);
                    $conn->query("UPDATE `products` SET `gst` = $tax_id WHERE `id` = $pid");
                }
            } else {
                // If products unchecked completely, remove this tax from all products
                $conn->query("UPDATE `products` SET `gst` = NULL WHERE `gst` = $tax_id");
            }

            // If applying to add-on services, update gst column for selected addons
            if (in_array('add-on-services', $apply_to) && !empty($addon_items)) {
                // First clear gst for addons not selected
                $conn->query("UPDATE `add-on-service` SET `gst` = NULL WHERE `gst` = $tax_id");

                // Set gst for selected addons
                foreach ($addon_items as $aid) {
                    $aid = intval($aid);
                    $conn->query("UPDATE `add-on-service` SET `gst` = $tax_id WHERE `id` = $aid");
                }
            } else {
                // If addons unchecked completely, remove this tax from all addons
                $conn->query("UPDATE `add-on-service` SET `gst` = NULL WHERE `gst` = $tax_id");
            }

            $msg = ($action === 'create') ? 'Tax Added' : 'Tax Updated';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $stmt->error]);
        }

        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM `taxes` WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Tax Deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}
?>

<style>
.form-check { padding: 10px; }
.form-check-label { margin: -2px 10px; }
input[type=number] { -moz-appearance: textfield; }
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>

<div class="dashboard-main-body">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <a class="cursor-pointer fw-bold" onclick="history.back()">
      <span class="fa fa-arrow-left"></span>&nbsp; Back
    </a>
    <h6 class="fw-semibold mb-0">Taxes</h6>
    <button type="button" id="addNewBtn" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
      <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
      Add New Tax
    </button>
  </div>

  <div class="card h-100 p-0 radius-12">
    <div class="card-body">
      <div class="table-responsive scroll-sm">
        <table class="table bordered-table mb-0" id="role-table">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Region</th>
              <th scope="col">Rate</th>
              <th scope="col">Type</th>
              <!-- <th scope="col">Applies</th> -->
              <th scope="col">Default</th>
              <th scope="col" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $service = "SELECT * FROM `taxes`"; 
            $results = $conn->query($service);
            if (mysqli_num_rows($results) > 0) {
              while ($row = mysqli_fetch_assoc($results)) {
                $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
              <td><?= htmlspecialchars($row['tax_name']); ?></td>
              <td><?= htmlspecialchars($row['country/region']); ?></td>
              <td><?= htmlspecialchars($row['rate']); ?></td>
              <td><?= htmlspecialchars($row['type']); ?></td>
              <!-- <td><?= htmlspecialchars($row['services']); ?></td> -->
              <td><?= htmlspecialchars($row['set_default']); ?></td>
              <td class="text-center">
                <div class="d-flex align-items-center gap-10 justify-content-center">
                  <button type="button" class="fa fa-edit editBtn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-row="<?= $json ?>"></button>
                  <button type="button" class="fa fa-trash-alt deleteBtn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>"></button>
                </div>
              </td>
            </tr>
            <?php 
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Start -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add New Tax</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="serviceForm">
          <input type="hidden" id="serviceId" name="id">

          <div class="mb-3">
            <label for="serviceName" class="form-label">Tax Name</label>
            <input type="text" class="form-control" id="serviceName" name="name" required>
          </div>

          <div class="mb-3">
            <label for="countryRegion" class="form-label">Country / Region</label>
            <select class="form-control" id="countryRegion" name="country" required>
              <option value="All">All (fallback)</option>
              <option value="India">India</option>
              <option value="United States">United States</option>
              <option value="United Kingdom">United Kingdom</option>
              <option value="Canada">Canada</option>
              <option value="Australia">Australia</option>
            </select>
          </div>

          <div class="mb-3">
            <div class="d-flex align-items-center gap-3">
              <div style="width: 50%;">
                <label for="rate" class="form-label">Rate (%)</label>
                <input type="number" class="form-control radius-8" id="rate" name="rate" min="0" step="0.01" required>
              </div>
              <div style="width: 50%;">
                <label for="taxType" class="form-label">Type</label>
                <select class="form-control radius-8" id="taxType" name="type" required>
                  <option value="exclusive">Exclusive (add on)</option>
                  <option value="inclusive">Inclusive (included in price)</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label d-block mb-2">Apply to:</label>
            <div class="d-flex align-items-start flex-wrap gap-4">
              <div class="form-check me-3">
                <input class="form-check-input apply-toggle" type="checkbox" id="applyPackages" name="apply_to[]" value="packages">
                <label class="form-check-label fw-semibold" for="applyPackages">Packages</label>
                <div id="packageList" class="mt-1 ms-4 d-none">
                  <?php foreach ($packages as $pkg): ?>
                    <div class="form-check">
                      <input class="form-check-input pkg-item" type="checkbox" name="package_items[]" value="<?= $pkg['id'] ?>">
                      <label class="form-check-label"><?= htmlspecialchars($pkg['name']) ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="form-check me-3">
                <input class="form-check-input apply-toggle" type="checkbox" id="applyProducts" name="apply_to[]" value="products">
                <label class="form-check-label fw-semibold" for="applyProducts">Products</label>
                <div id="productList" class="mt-1 ms-4 d-none">
                  <?php foreach ($products as $prd): ?>
                    <div class="form-check">
                      <input class="form-check-input prd-item" type="checkbox" name="product_items[]" value="<?= $prd['id'] ?>">
                      <label class="form-check-label"><?= htmlspecialchars($prd['name']) ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="form-check">
                <input class="form-check-input apply-toggle" type="checkbox" id="applyAddons" name="apply_to[]" value="add-on-services">
                <label class="form-check-label fw-semibold" for="applyAddons">Add-on Services</label>
                <div id="addonList" class="mt-1 ms-4 d-none">
                  <?php foreach ($addons as $ad): ?>
                    <div class="form-check">
                      <input class="form-check-input addon-item" type="checkbox" name="addon_items[]" value="<?= $ad['id'] ?>">
                      <label class="form-check-label"><?= htmlspecialchars($ad['name']) ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="setDefaultTax" name="set_default" value="1">
              <label class="form-check-label" for="setDefaultTax">Set as default tax</label>
            </div>
            <small class="text-muted d-block mt-1">
              Default tax will be pre-selected on new invoices for matching region.
            </small>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id="submitService" class="btn lufera-bg">Save Tax</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
    $('#role-table').DataTable();
});
document.addEventListener('DOMContentLoaded', function() {
  const modalEl = document.getElementById('exampleModal');
  const form = document.getElementById('serviceForm');
  const submitBtn = document.getElementById('submitService');
  const addBtn = document.getElementById('addNewBtn');

  addBtn.addEventListener('click', () => {
    form.reset();
    document.getElementById('serviceId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Tax';
    document.querySelectorAll('#packageList, #productList, #addonList').forEach(e=>e.classList.add('d-none'));
  });

  document.querySelectorAll('.apply-toggle').forEach(cb => {
    cb.addEventListener('change', () => {
      const map = {
        'applyPackages': 'packageList',
        'applyProducts': 'productList',
        'applyAddons': 'addonList'
      };
      const target = document.getElementById(map[cb.id]);
      if (target) target.classList.toggle('d-none', !cb.checked);
    });
  });

  submitBtn.addEventListener('click', () => {
    const fd = new FormData(form);
    fd.append('action', form.serviceId.value ? 'update' : 'create');
    fetch('', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({ icon: 'success', title: data.message, confirmButtonText: 'OK' })
            .then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
      });
  });

  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = JSON.parse(btn.dataset.row);
      form.reset();
      form.serviceId.value = row.id;
      form.serviceName.value = row['tax_name'];
      form.countryRegion.value = row['country/region'];
      form.rate.value = row.rate;
      form.taxType.value = row.type;
      form.setDefaultTax.checked = row.set_default == 1;
      document.getElementById('modalTitle').textContent = 'Edit Tax';

      try {
        const s = JSON.parse(row.services || '{}');
        if (s.apply_to) {
          s.apply_to.forEach(v => {
            if (v === 'packages') {
              document.getElementById('applyPackages').checked = true;
              document.getElementById('packageList').classList.remove('d-none');
              if (s.packages) s.packages.forEach(id=>{
                document.querySelector(`.pkg-item[value="${id}"]`)?.setAttribute('checked','checked');
              });
            }
            if (v === 'products') {
              document.getElementById('applyProducts').checked = true;
              document.getElementById('productList').classList.remove('d-none');
              if (s.products) s.products.forEach(id=>{
                document.querySelector(`.prd-item[value="${id}"]`)?.setAttribute('checked','checked');
              });
            }
            if (v === 'add-on-services') {
              document.getElementById('applyAddons').checked = true;
              document.getElementById('addonList').classList.remove('d-none');
              if (s.addons) s.addons.forEach(id=>{
                document.querySelector(`.addon-item[value="${id}"]`)?.setAttribute('checked','checked');
              });
            }
          });
        }
      } catch(e){ console.error(e); }

      new bootstrap.Modal(modalEl).show();
    });
  });

  document.querySelectorAll('.deleteBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the tax.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
      }).then(res => {
        if (res.isConfirmed) {
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('id', id);
          fetch('', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
              if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: data.message, confirmButtonText: 'OK' })
                  .then(() => location.reload());
              } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
              }
            });
        }
      });
    });
  });
});
</script>

<?php include './partials/layouts/layoutBottom.php'; ?>
