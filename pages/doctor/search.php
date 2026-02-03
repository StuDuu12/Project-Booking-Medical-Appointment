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
          background-image:
            linear-gradient(135deg, rgba(254, 243, 199, 0.85) 0%, rgba(254, 215, 170, 0.85) 25%, rgba(253, 186, 116, 0.85) 50%, rgba(251, 146, 60, 0.85) 75%, rgba(249, 115, 22, 0.85) 100%),
            url('../../images/ngua.png');
          background-size: cover, contain;
          background-position: center, center;
          background-repeat: no-repeat, no-repeat;
          background-attachment: fixed, fixed;
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

        .petals-container {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          overflow: hidden;
          pointer-events: none;
          z-index: 9999;
        }

        .petal {
          position: absolute;
          top: -10px;
          width: 15px;
          height: 15px;
          background: radial-gradient(ellipse at center, #ffb7d5 0%, #ff69b4 40%, #ff1493 100%);
          border-radius: 50% 0 50% 0;
          opacity: 0.8;
          animation: fall linear infinite;
        }

        .petal::before {
          content: '';
          position: absolute;
          width: 100%;
          height: 100%;
          background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.5) 0%, transparent 50%);
          border-radius: 50% 0 50% 0;
          transform: rotate(90deg);
        }

        @keyframes fall {
          0% {
            transform: translateY(0) rotateZ(0deg);
            opacity: 0.8;
          }

          100% {
            transform: translateY(100vh) rotateZ(360deg);
            opacity: 0;
          }
        }

        .petal:nth-child(odd) {
          animation-duration: 8s;
        }

        .petal:nth-child(even) {
          animation-duration: 12s;
        }

        .petal:nth-child(3n) {
          animation-duration: 10s;
        }

        .petal:nth-child(5n) {
          animation-duration: 15s;
        }
      </style>
    </head>

    <body>
      <div class="petals-container" id="petals"></div>
      <script>
        function createPetals() {
          const c = document.getElementById('petals');
          for (let i = 0; i < 25; i++) {
            const p = document.createElement('div');
            p.className = 'petal';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDelay = Math.random() * 10 + 's';
            p.style.animationDuration = (8 + Math.random() * 10) + 's';
            c.appendChild(p);
          }
        }
        window.addEventListener('load', createPetals);
      </script>
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