<?php include './partials/layouts/layoutTop.php' ?>

<?php
  $Id = $_SESSION['user_id'];

  $stmt = $conn->prepare("SELECT user_id, business_name, role FROM users WHERE id = ?");
  $stmt->bind_param("i", $Id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $UserId = $row['user_id']; 
  $BusinessName = $row['business_name'];
  $role = $row['role'];
  $stmt->close();

  $stmt = $conn->prepare("SELECT plan, duration, status, created_at FROM websites WHERE user_id = ?");
  $stmt->bind_param("s", $UserId); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $Plan = $row['plan'];
  $Duration = $row['duration'];
  $Status = strtolower($row['status'] ?? 'Pending');
  $CreatedAt = $row['created_at'];
  $stmt->close();

  $startDate = new DateTime($CreatedAt);
  $endDate = (clone $startDate)->modify("+$Duration");
  $Validity = $startDate->format("d-m-Y") . " to " . $endDate->format("d-m-Y");

  switch ($Status) {
    case 'active':
      $statusClass = 'text-success'; 
      break;
    case 'expired':
      $statusClass = 'text-danger'; 
      break;
    case 'pending':
    default:
      $statusClass = 'text-pending'; 
      break;
  }
?>

<style>
  .btn-upgrade {
    background-color: #fff9c4; /* light yellow */
    color: #000;
    border: 1px solid #ccc;
  }

  .btn-edit-website {
    background-color: #fec700;
    color: #000;
    border: none;
  }

  .btn-upgrade:hover {
    background-color: #f0e68c; /* slightly darker on hover */
  }

  .btn-edit-website:hover {
    background-color: #e6be00; /* slightly darker on hover */
  }

  .icon-black {
    color: #000;
  }

  .plan-details-shadow {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    background-color: #fff;
  }

  .btn-copy-ip {
    background: none;
    border: none;
    padding: 0;
    color: #555;
    cursor: pointer;
    display: flex;
    align-items: center;
  }
  .btn-copy-ip:hover {
    color: #000;
  }

  .text-pending {
    color: #ff9800;
  }
</style>

        <div class="dashboard-main-body">

            <!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Radio</h6>
                <ul class="d-flex align-items-center gap-2">
                    <li class="fw-medium">
                        <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                            Dashboard
                        </a>
                    </li>
                    <li>-</li>
                    <li class="fw-medium">Components / Radio</li>
                </ul>
            </div> -->

            <div class="mb-24 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <h6 class="fw-semibold mb-0"><?php echo htmlspecialchars($BusinessName); ?></h6>
                <span>|</span>
                <iconify-icon icon="mdi:home-outline" class="text-lg icon-black"></iconify-icon>
                <!-- <span class="text-warning">luferatech.com</span> -->
                <span class="text-warning">N/A</span>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-upgrade">Upgrade</button>
                <?php 
                if($role != '1'){
                ?>
                <a href="forms.php"><button type="button" class="btn btn-sm btn-edit-website">Edit Website</button></a>
                <?php 
                } else{
                ?>
                <button type="button" class="btn btn-sm btn-edit-website">Edit Website</button>
                <?php 
                }
                ?>
              </div>
            </div>

            <div class="row gy-4">
                <div class="col-lg-6">
                  <div class="card h-100 p-0">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                      <h6 class="text-lg fw-semibold mb-0">Plan Details:</h6>
                    </div>
                    <div class="card-body p-24 plan-details-shadow bg-base">
                      <div class="d-flex justify-content-between mb-3">
                        <span>Plan Name</span>
                        <span><?php echo htmlspecialchars($Plan); ?></span>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between my-3">
                        <span>Validity</span>
                        <span><?php echo $Validity; ?></span>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between mt-3">
                        <span>Status</span>
                        <span class="fw-semibold <?php echo $statusClass; ?>"><?php echo ucfirst($Status); ?></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="card h-100 p-0">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                      <h6 class="text-lg fw-semibold mb-0">Website Details:</h6>
                    </div>
                    <div class="card-body p-24 plan-details-shadow bg-base">
                      <div class="d-flex justify-content-between mb-3">
                        <span>Access your website at</span>
                        <!-- <span>https://lehotelhost.com</span> -->
                        <span>N/A</span>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between my-3">
                        <span>Access your website with www</span>
                        <!-- <span>https://www.lehotelhost.com</span> -->
                         <span>N/A</span>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between align-items-center mt-3">
                        <span>Website IP address</span>
                        <span class="d-flex align-items-center gap-2">
                          <!-- <span>46.25.89.58.78</span> -->
                           <span>N/A</span>
                          <button
                            type="button"
                            class="btn-copy-ip"
                            onclick="copyIP('N/A')"
                            title="Copy IP Address"
                            aria-label="Copy IP Address"
                          >
                            <iconify-icon
                              icon="mdi:content-copy"
                              style="cursor:pointer; font-size: 18px;"
                            ></iconify-icon>
                          </button>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="card h-100 p-0">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                      <h6 class="text-lg fw-semibold mb-0">Nameservers:</h6>
                    </div>
                    <div class="card-body p-24 plan-details-shadow bg-base">
                      <div class="d-flex justify-content-between mb-3">
                        <span>Current nameserver 1</span>
                        <span class="d-flex align-items-center gap-2">
                          <!-- <span>ns1.dns-parking.com</span> -->
                          <span>N/A</span>
                          <button
                            type="button"
                            class="btn-copy-ip"
                            onclick="copyIP('N/A')"
                            title="Copy nameserver 1"
                            aria-label="Copy nameserver 1"
                          >
                            <iconify-icon
                              icon="mdi:content-copy"
                              style="cursor:pointer; font-size: 18px;"
                            ></iconify-icon>
                          </button>
                        </span>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between my-3">
                        <span>Current nameserver 2</span>
                        <span class="d-flex align-items-center gap-2">
                          <!-- <span>ns2.dns-parking.com</span> -->
                          <span>N/A</span>
                          <button
                            type="button"
                            class="btn-copy-ip"
                            onclick="copyIP('N/A')"
                            title="Copy nameserver 2"
                            aria-label="Copy nameserver 2"
                          >
                            <iconify-icon
                              icon="mdi:content-copy"
                              style="cursor:pointer; font-size: 18px;"
                            ></iconify-icon>
                          </button>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
        </div>

        <script>
          function copyIP(text) {
            navigator.clipboard.writeText(text).then(() => {
              alert('Copied: ' + text);
            }).catch(() => {
              alert('Failed to copy');
            });
          }
        </script>

<?php include './partials/layouts/layoutBottom.php' ?>