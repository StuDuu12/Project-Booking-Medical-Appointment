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

      <!-- Hiệu ứng hoa đào rơi tráng lệ & quý phái - Premium Edition -->
      <script type="text/javascript">
        (function() {
          const isMobile = window.matchMedia('(max-width: 576px)').matches;
          const isTablet = window.matchMedia('(min-width: 577px) and (max-width: 992px)').matches;
          const petalCount = isMobile ? 15 : (isTablet ? 30 : 50);
          const petalImage = 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEizrrtX-KQtKY8e8pxCHjLROT5pYW7sVkUpET9HHpW8QO-PnoIRKVsvRDxM6shrE4Q-44Oh9teSGK1SApaZ1OJvhR4z7ENgKSJOLWfsdKw9jPszAa2HqaE6W8ohyGHRvff6TgKXEUjnn73LLLp3FHbtMTJnIkPxPhujWwG5ZsFgW7ctQ0zrR5KKSqlewg/s16000/hoadao-anonyviet.com.png';

          const petals = [];
          let docWidth = window.innerWidth;
          let docHeight = window.innerHeight;

          for (let i = 0; i < petalCount; i++) {
            const size = 10 + Math.random() * 14;
            const opacity = 0.5 + Math.random() * 0.4;
            const rotation = Math.random() * 360;
            const blur = Math.random() * 0.5;

            const petal = {
              x: Math.random() * docWidth,
              y: Math.random() * docHeight - docHeight,
              dx: 0,
              rotation: rotation,
              rotationSpeed: (Math.random() - 0.5) * 1.5,
              amplitude: 20 + Math.random() * 35,
              speedX: 0.01 + Math.random() / 15,
              speedY: 0.3 + Math.random() * 0.6,
              size: size,
              opacity: opacity,
              blur: blur,
              element: null
            };

            const div = document.createElement('div');
            div.id = 'cherry-petal-' + i;
            div.style.cssText = `position:fixed;z-index:9998;visibility:visible;pointer-events:none;width:${size}px;left:${petal.x}px;top:${petal.y}px;opacity:${opacity};transition:transform 0.15s ease-out;will-change:transform,top,left`;
            div.innerHTML = `<img src="${petalImage}" alt="Hoa đào" style="width:100%;height:auto;transform:rotate(${rotation}deg);filter:drop-shadow(2px 2px 4px rgba(255,105,180,0.4)) blur(${blur}px) brightness(1.1);">`;
            document.body.appendChild(div);
            petal.element = div;
            petals.push(petal);
          }

          function animate() {
            docWidth = window.innerWidth;
            docHeight = window.innerHeight;

            petals.forEach(petal => {
              petal.y += petal.speedY;
              petal.rotation += petal.rotationSpeed;

              if (petal.y > docHeight + 80) {
                petal.x = Math.random() * docWidth;
                petal.y = -80;
                petal.speedX = 0.01 + Math.random() / 15;
                petal.speedY = 0.3 + Math.random() * 0.6;
                petal.rotationSpeed = (Math.random() - 0.5) * 1.5;
              }

              petal.dx += petal.speedX;
              const swayX = petal.x + petal.amplitude * Math.sin(petal.dx);
              const scaleEffect = 0.95 + Math.sin(petal.dx * 2) * 0.05;

              petal.element.style.top = petal.y + 'px';
              petal.element.style.left = swayX + 'px';
              petal.element.querySelector('img').style.transform = `rotate(${petal.rotation}deg) scale(${scaleEffect})`;
            });

            requestAnimationFrame(animate);
          }

          animate();

          let resizeTimer;
          window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
              docWidth = window.innerWidth;
              docHeight = window.innerHeight;
            }, 250);
          });
        })();
      </script>
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