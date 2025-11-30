<?php
/**
 * Customer - My Reviews
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_CUSTOMER]);

$pageTitle = 'My Reviews';
$db = new Database();
$conn = $db->getConnection();

$customerId = $_SESSION['customer_id'];
$message = '';

// Handle new review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $conn->prepare("INSERT INTO review (customer_id, product_id, order_id, rating, title, comment, is_verified_purchase)
                                VALUES (?, ?, ?, ?, ?, ?, TRUE)
                                ON DUPLICATE KEY UPDATE rating = VALUES(rating), title = VALUES(title), comment = VALUES(comment)");
        $stmt->execute([
            $customerId,
            $_POST['product_id'],
            $_POST['order_id'] ?: null,
            $_POST['rating'],
            sanitize($_POST['title']),
            sanitize($_POST['comment'])
        ]);
        $message = 'Review submitted successfully!';
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// Get my reviews
$reviews = $conn->prepare("SELECT r.*, p.name AS product_name, p.image_url
                           FROM review r
                           JOIN product p ON r.product_id = p.product_id
                           WHERE r.customer_id = ?
                           ORDER BY r.created_at DESC");
$reviews->execute([$customerId]);
$reviews = $reviews->fetchAll();

// Check if adding new review
$newReview = null;
if (isset($_GET['product']) && isset($_GET['order'])) {
    // Check if product is from customer's order
    $stmt = $conn->prepare("SELECT p.*, oi.order_id FROM order_item oi
                            JOIN product p ON oi.product_id = p.product_id
                            JOIN customer_order co ON oi.order_id = co.order_id
                            WHERE oi.product_id = ? AND oi.order_id = ? AND co.customer_id = ? AND co.status = 'Delivered'");
    $stmt->execute([$_GET['product'], $_GET['order'], $customerId]);
    $newReview = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-star"></i> My Reviews</h1>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($newReview): ?>
<!-- New Review Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Write a Review</h5>
    </div>
    <div class="card-body">
        <h6><?php echo htmlspecialchars($newReview['name']); ?></h6>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="product_id" value="<?php echo $newReview['product_id']; ?>">
            <input type="hidden" name="order_id" value="<?php echo $newReview['order_id']; ?>">

            <div class="mb-3">
                <label class="form-label">Rating *</label>
                <div class="rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" style="cursor: pointer; font-size: 1.5rem; color: #ddd;">
                        <i class="bi bi-star-fill"></i>
                    </span>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" value="5" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" maxlength="150">
            </div>

            <div class="mb-3">
                <label class="form-label">Review</label>
                <textarea name="comment" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Review</button>
            <a href="reviews.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- My Reviews List -->
<?php if (empty($reviews) && !$newReview): ?>
<div class="empty-state">
    <i class="bi bi-star"></i>
    <p>You haven't written any reviews yet</p>
    <p class="text-muted">Order products and write reviews after delivery</p>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($reviews as $review): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($review['product_name']); ?></h6>

                <div class="star-rating mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star-fill <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>

                <?php if ($review['title']): ?>
                <h6><?php echo htmlspecialchars($review['title']); ?></h6>
                <?php endif; ?>

                <?php if ($review['comment']): ?>
                <p class="text-muted"><?php echo htmlspecialchars($review['comment']); ?></p>
                <?php endif; ?>

                <small class="text-muted">
                    <?php echo formatDateTime($review['created_at']); ?>
                    <?php if ($review['is_verified_purchase']): ?>
                    <span class="badge bg-success">Verified Purchase</span>
                    <?php endif; ?>
                    <?php if ($review['is_approved']): ?>
                    <span class="badge bg-info">Published</span>
                    <?php else: ?>
                    <span class="badge bg-warning">Pending</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
