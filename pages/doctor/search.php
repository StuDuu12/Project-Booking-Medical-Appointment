<?php
session_start();
require_once '../../config.php';

if (isset($_POST['search_submit'])) {
  try {
    $contact = htmlspecialchars(trim($_POST['contact']));
    $docname = $_SESSION['dname'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM appointmenttb WHERE contact = :contact AND doctor = :doctor");
    $stmt->execute([':contact' => $contact, ':doctor' => $docname]);
    $results = $stmt->fetchAll();

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css">
      <style>
        body {
          background-color: #342ac1;
          color: white;
          text-align: center;
          padding-top: 50px;
        }

        .container {
          text-align: left;
        }

        h3 {
          margin-bottom: 20px;
        }

        .back-btn {
          margin-top: 20px;
        }

        .no-results {
          text-align: center;
          padding: 20px;
          background: rgba(255, 255, 255, 0.1);
          border-radius: 5px;
          margin: 20px 0;
        }
      </style>
    </head>

    <body>
      <div class="container">
        <h3>Kết quả tìm kiếm</h3>
        <?php
        if (count($results) > 0) {
        ?>
          <table class="table table-hover table-dark">
            <thead>
              <tr>
                <th>Họ</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Liên hệ</th>
                <th>Ngày hẹn</th>
                <th>Giờ hẹn</th>Ư
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($results as $row) {
                $fname = htmlspecialchars($row['fname']);
                $lname = htmlspecialchars($row['lname']);
                $email = htmlspecialchars($row['email']);
                $contact = htmlspecialchars($row['contact']);
                $appdate = htmlspecialchars($row['appdate']);
                $apptime = htmlspecialchars($row['apptime']);
                echo '<tr>
                    <td>' . $fname . '</td>
                    <td>' . $lname . '</td>
                    <td>' . $email . '</td>
                    <td>' . $contact . '</td>
                    <td>' . $appdate . '</td>
                    <td>' . $apptime . '</td>
                </tr>';
              }
              ?>
            </tbody>
          </table>
        <?php
        } else {
          echo '<div class="no-results">
                <p>Không tìm thấy lịch hẹn cho số điện thoại này.</p>
            </div>';
        }
        ?>
        <div class="back-btn">
          <a href="dashboard.php" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Quay lại bảng điều khiển
          </a>
        </div>
      </div>

      <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
    </body>

    </html>
<?php
  } catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo '<div class="alert alert-danger" role="alert">
          Lỗi tìm kiếm lịch hẹn. Vui lòng thử lại.
      </div>';
  }
}
?>