<?php include './partials/layouts/layoutTop.php' ?>

<style>
    .tagline{
        border-bottom:1px solid #fec700;
    }
    .plan-details-table tbody tr td{
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width: 50%;
    }
    .ad-box{
        background: lightgoldenrodyellow;
        padding: 2px;
        border: 1px solid;
        margin: 10px 0 0;
    }


   
   .payment-wrapper {
    padding-left: 28px; /* space for checkbox */
    position: relative;
  }

  .payment-checkbox {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    cursor: pointer;
    z-index: 1;
  }

  .payment-box {
    border: 1.5px solid #101010;
    border-radius: 6px;
    min-width: 160px;
    padding: 10px 16px;
    cursor: pointer;
    user-select: none;
  }
</style>



<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Your Cart</h6>        
    </div>

    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                    <h6 class="mb-0">Business</h6>
                    <p class="mb-0">Perfect plan to get started for your own Website</p>
                </div>
                    <div class="card-body p-16">
                        <table class="plan-details-table mb-0 w-100">
                            <tbody>
                                <tr>
                                    <td>Period</td>
                                    <td>1 Year</td>
                                </tr>
                                <tr>
                                    <td>Validity</td>
                                    <td>
                                        2025-05-12
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border-0" colspan="2">Renews at $1500/year for 3 Years
                                        <p class="text-sm ad-box">Great news! Your FREE domain + 3 months FREE are included with this order</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                    <div class="card-header py-10 border-none d-flex justify-content-between" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <div class="">
                            <h6 class="mb-0">Sub Total</h6>
                            <p class="mb-0">Sub total does not include applicable taxes</p>
                        </div>
                        <div class="align-content-center">
                            <h4 class="mb-0">1500</h4>
                        </div>
                    </div>
                    <div class="card-body p-16">
                        <table class="plan-details-table mb-0 w-100">
                            <tbody>
                                <tr>
                                    <td>Discount</td>
                                    <td class="text-end">N/A</td>
                                </tr>
                                <tr>
                                    <td>Tax (GST 18%)</td>
                                    <td class="text-end">1589</td>
                                </tr>
                                <tr>
                                    <td class="border-0">Estimated Total</td>
                                    <td class="border-0 text-end">2500</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- <div class="row gy-4 mt-4"> -->
                <!-- <div class="col-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                            <h6 class="mb-0">Third Card</h6>
                            <p class="mb-0">This is a full-width card below the first two.</p>
                        </div>
                        <div class="card-body p-16">
                            <p>You can put any content here, such as additional services, upsells, FAQs, etc.</p>
                        </div>
                    </div>
                </div> -->

                <div class="col-12">
  <div class="card h-100 radius-12">
    <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
      <h6 class="mb-0">Select payment mode</h6>
      <p class="mb-0">Orders summary includes discounts and taxes</p>
    </div>
    <div class="card-body p-16">
      <div class="d-flex gap-4 flex-wrap">

        <!-- Payment Option 1 -->
        <div class="position-relative payment-wrapper">
          <input type="checkbox" id="bankTransfer" class="payment-checkbox" />
          <label for="bankTransfer" class="payment-box d-flex align-items-center">
            Bank transfer
            <span class="ms-2">&#9662;</span>
          </label>
        </div>

        <!-- Payment Option 2 -->
        <div class="position-relative payment-wrapper">
          <input type="checkbox" id="directPay" class="payment-checkbox" />
          <label for="directPay" class="payment-box d-flex align-items-center">
            Direct pay
            <span class="ms-2">&#9662;</span>
          </label>
        </div>

        <!-- Payment Option 3 -->
        <div class="position-relative payment-wrapper">
          <input type="checkbox" id="paypal" class="payment-checkbox" />
          <label for="paypal" class="payment-box d-flex align-items-center">
            Paypal
            <span class="ms-2">&#9662;</span>
          </label>
        </div>

        <!-- Payment Option 4 -->
        <div class="position-relative payment-wrapper">
          <input type="checkbox" id="card" class="payment-checkbox" />
          <label for="card" class="payment-box d-flex align-items-center">
            Card
            <span class="ms-2">&#9662;</span>
          </label>
        </div>

      </div>
    </div>
  </div>
</div>


            <!-- </div> -->

        </div>
    </div> 

</div>



<script>
    $('#updateForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: 'update.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#result').html(response);
            loadUserData(); // Reload user data after update
        },
        error: function(xhr) {
            $('#result').html("Error updating data.");
        }
    });
});

loadUserData();
</script>

<?php include './partials/layouts/layoutBottom.php' ?>