<?php include './partials/layouts/layoutTop.php' ?>


<style>
    body {
        background-color: #f3f4f9 !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .full-width-page {
        width: 100% !important;
        padding: 0 20px !important;
        box-sizing: border-box !important;
    }

    .product-form-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        /* background: #fff !important; */
        padding: 40px 30px !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .product-form-container h2 {
        text-align: left !important;
        color: #222 !important;
        font-size: 28px !important;
        margin-bottom: 30px !important;
    }

    .product-form-container label {
        display: block !important;
        margin-bottom: 6px !important;
        color: #333 !important;
        font-weight: 600 !important;
    }

    .product-form-container input,
    .product-form-container select,
    .product-form-container textarea {
        width: 100% !important;
        padding: 12px 16px !important;
        margin-bottom: 20px !important;
        border: 1px solid #ccc !important;
        border-radius: 6px !important;
        background: #f9fafc !important;
        font-size: 16px !important;
    }

    .product-form-container textarea {
        resize: vertical !important;
        min-height: 100px !important;
    }

    .product-form-container button {
        background-color: #ffcc00 !important;
        border: none !important;
        color: #000 !important;
        padding: 14px 24px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        width: 100% !important;
        transition: background 0.3s ease !important;
        text-align: center;
    }

    .product-form-container button:hover {
        background-color: #e6b800 !important;
    }
</style>

<div class="full-width-page">
    <div class="product-form-container">
        <h2>Add Product</h2>
        <form action="save-product.php" method="post" enctype="multipart/form-data">
            <label for="product_image">Product Image</label>
            <input type="file" id="product_image" name="product_image" accept="image/*" required>

            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required>

            <label for="sku">SKU</label>
            <input type="text" id="sku" name="sku" placeholder="e.g., PRO-12345" required>

            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">-- Select Category --</option>
                <option value="electronics">Electronics</option>
                <option value="fashion">Fashion</option>
                <option value="home">Home & Kitchen</option>
                <option value="books">Books</option>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>

            <label for="price">Price ($)</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <label for="sale_price">Sale Price ($)</label>
            <input type="number" id="sale_price" name="sale_price" step="0.01">

            <label for="stock">Stock Quantity</label>
            <input type="number" id="stock" name="stock" required>

            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" placeholder="e.g., new, featured, summer">

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>

            <button type="submit">Buy Now</button>
        </form>
    </div>
</div>


<?php include './partials/layouts/layoutBottom.php' ?>
