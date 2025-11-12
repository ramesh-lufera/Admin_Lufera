<?php include './partials/layouts/layoutTop.php' ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>eCommerce Product Detail</title>
    <style>
        
/*****************globals*************/

img {
  max-width: 100%; }
.prod-img{
    min-height: 455px;
    max-height: 455px;
    object-fit: cover;
}
.preview {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column; }
  @media screen and (max-width: 996px) {
    .preview {
      margin-bottom: 20px; } }

.preview-pic {
  -webkit-box-flex: 1;
  -webkit-flex-grow: 1;
      -ms-flex-positive: 1;
          flex-grow: 1; }

.tab-content {
  overflow: hidden; }
  .tab-content img {
    width: 100%;
    -webkit-animation-name: opacity;
            animation-name: opacity;
    -webkit-animation-duration: .3s;
            animation-duration: .3s; }
@media screen and (min-width: 997px) {
  .wrapper {
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex; } }

.details {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column; 
  justify-content: space-between;
}

.checked, .price span {
  color: #fec700; }

.product-title, .rating, .product-description, .price, .vote, .sizes {
  margin-bottom: 15px; }

.product-title {
  margin-top: 0; }

.colors, .price {
  font-size: 20px !important;
}

@-webkit-keyframes opacity {
  0% {
    opacity: 0;
    -webkit-transform: scale(3);
            transform: scale(3); }
  100% {
    opacity: 1;
    -webkit-transform: scale(1);
            transform: scale(1); } }

@keyframes opacity {
  0% {
    opacity: 0;
    -webkit-transform: scale(3);
            transform: scale(3); }
  100% {
    opacity: 1;
    -webkit-transform: scale(1);
            transform: scale(1); } }

/*# sourceMappingURL=style.css.map */
    </style>
  </head>
  <?php
      $id = $_GET['id'];
      $query = "SELECT * FROM products where id = $id";
      $result = $conn ->query($query);
      $row = $result->fetch_assoc();

      // Get active symbol
      $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
      $symbol = "$"; // default
      if ($row1 = $result1->fetch_assoc()) {
          $symbol = $row1['symbol'];
      }

      $result1 = mysqli_query($conn, $query);
  ?>
  <body>
  <div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Product</h6>
    </div>
	<div class="container">
		<div class="card">
			<div class="card-body p-40">
        <div class="container">
				<div class="wrapper row">
					<div class="preview col-md-6">
						
						<div class="preview-pic tab-content">
						  <div id="pic-1"><img src="uploads/products/<?php echo $row['product_image'] ?>" class="prod-img rounded-4" /></div>
						  
						</div>
						
					</div>
					<div class="details col-md-6">
						<h3 class="product-title fs-1"><?php echo $row['name'] ?></h3>
            <h6 class="product-title"><?php echo $row['subtitle'] ?></h6>
						
						<p class="product-description"><?php echo $row['description'] ?>.</p>
						<h4 class="price" id="currency-symbol-display">Price: <span><?= htmlspecialchars($symbol) ?><?php echo $row['price'] ?></span></h4>
            <h4 class="price">Validity: <span><?php echo $row['duration'] ?></span></h4>
						
						<h6 class="colors">Category: <?php echo $row['category'] ?></h6>
            <h6 class="colors">Tags: <?php echo $row['tags'] ?></h6>
						<div class="action">
							<!-- <button class="add-to-cart btn btn-default" type="button">Get Started</button> -->
              <form action="cart.php" method="POST">
                  <input type="hidden" name="type" value="product">  
                  <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                  <input type="hidden" name="plan_name" value="<?= htmlspecialchars($row['name']) ?>">
                  <input type="hidden" name="title" value="<?= htmlspecialchars($row['title']) ?>">
                  <input type="hidden" name="subtitle" value="<?= htmlspecialchars($row['subtitle']) ?>">
                  <input type="hidden" name="price" value="<?= htmlspecialchars($row['price']) ?>">
                  <input type="hidden" name="duration" value="<?= htmlspecialchars($row['duration']) ?>">
                  <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                  <input type="hidden" name="gst" value="<?= htmlspecialchars($row['gst']) ?>">

                  <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8">Get started</button>
              </form>
							<!-- <button class="like btn btn-default" type="button"><span class="fa fa-heart"></span></button> -->
						</div>
					</div>
				</div>
        </div>
			</div>
		</div>
	</div>
    </div>
  </body>
</html>
<?php include './partials/layouts/layoutBottom.php' ?>