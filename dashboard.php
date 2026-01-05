<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.report_title LIKE ?
    ORDER BY r.report_year DESC, r.report_month DESC
");
$stmt->execute(["%$search%"]);
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h1>Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>

<form method="GET" action="dashboard.php">
    <input type="text" name="search" placeholder="Search reports"
           value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<hr>

<?php
$current = '';

foreach ($reports as $r):
    $group = date(
        "F Y",
        strtotime($r['report_year'] . '-' . $r['report_month'] . '-01')
    );

    if ($group !== $current):
        echo "<h3>$group</h3>";
        $current = $group;
    endif;
?>
    <div>
        📄 <?= htmlspecialchars($r['report_title']) ?>
        <a href="<?= htmlspecialchars($r['local_path']) ?>" target="_blank">View</a>

        <?php if (isAdmin()): ?>
            | <a href="delete_report.php?id=<?= $r['report_id'] ?>"
                 onclick="return confirm('Are you sure you want to delete this report?')">
                 Delete
              </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</body>
</html>
