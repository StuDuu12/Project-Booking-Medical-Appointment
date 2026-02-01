<?php
session_start();
// 1. Kết nối CSDL (Đảm bảo đường dẫn đúng)
if (file_exists('../../../config.php')) require_once('../../../config.php');
else require_once('../../config.php');

// 2. Kiểm tra ID bệnh nhân
if (!isset($_POST['pid'])) exit('Không tìm thấy ID bệnh nhân');
$pid = $_POST['pid'];

// 3. Lấy thông tin bệnh nhân
$stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = ?");
$stmt->execute([$pid]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) exit('Bệnh nhân không tồn tại');

// 4. Lấy lịch sử khám bệnh
$sql = "SELECT mr.*, d.fullname as doc_name, s.name_vi as spec_name
        FROM medical_records mr
        LEFT JOIN doctb d ON mr.doctor_id = d.id
        LEFT JOIN specializations s ON d.spec_id = s.id
        WHERE mr.patient_id = ?
        ORDER BY mr.record_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pid]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white border-bottom sticky-top" style="top:0; z-index:100; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <div>
        <h5 class="m-0 text-primary font-weight-bold">
            <?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?>
        </h5>
        <div class="small text-muted mt-1">
            <span class="mr-2"><i class="fas fa-id-card"></i> Mã: #<?php echo $patient['pid']; ?></span>
            <span class="mr-2"><i class="fas fa-phone"></i> <?php echo $patient['contact']; ?></span>
            <span><i class="fas fa-venus-mars"></i> <?php echo $patient['gender']; ?></span>
        </div>
    </div>
    <button class="btn btn-success btn-sm shadow-sm" onclick="openAddRecordModal(<?php echo $pid; ?>)">
        <i class="fas fa-plus-circle"></i> Thêm Phiếu Khám
    </button>
</div>

<?php if (count($records) > 0): ?>
    <div class="timeline p-2">
        <?php foreach ($records as $rec): ?>
            <div class="card mb-4 shadow-sm border-0" style="border-left: 4px solid #4e73df !important; border-radius: 8px;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="badge badge-primary mr-2 p-2">
                            <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($rec['record_date'])); ?>
                        </span>
                        <span class="text-dark font-weight-bold">BS. <?php echo htmlspecialchars($rec['doc_name'] ?? 'Chưa rõ'); ?></span>
                        <span class="text-muted small font-italic">(<?php echo htmlspecialchars($rec['spec_name'] ?? ''); ?>)</span>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-light text-primary border mr-1" onclick='editRecord(<?php echo json_encode($rec); ?>)' title="Chỉnh sửa">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger border" onclick="deleteRecord(<?php echo $rec['id']; ?>, <?php echo $pid; ?>)" title="Xóa phiếu">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body pt-2 pb-3">
                    <div class="row text-center mb-3 mx-0 rounded bg-white border py-2 small" style="background-color: #fcfcfc;">
                        <div class="col px-1 border-right"><span class="text-muted d-block" style="font-size:10px">CAO (cm)</span><b><?php echo $rec['height'] ?? '-'; ?></b></div>
                        <div class="col px-1 border-right"><span class="text-muted d-block" style="font-size:10px">NẶNG (kg)</span><b><?php echo $rec['weight'] ?? '-'; ?></b></div>
                        <div class="col px-1 border-right"><span class="text-muted d-block" style="font-size:10px">BMI</span><b><?php echo $rec['bmi'] ?? '-'; ?></b></div>
                        <div class="col px-1 border-right"><span class="text-muted d-block" style="font-size:10px">H.ÁP</span><b class="text-danger"><?php echo $rec['blood_pressure'] ?? '-'; ?></b></div>
                        <div class="col px-1 border-right"><span class="text-muted d-block" style="font-size:10px">MẠCH</span><b><?php echo $rec['heart_rate'] ?? '-'; ?></b></div>
                        <div class="col px-1"><span class="text-muted d-block" style="font-size:10px">NHIỆT ĐỘ</span><b><?php echo $rec['temperature'] ?? '-'; ?></b></div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-2">
                            <span class="badge badge-secondary p-1">Lý do khám:</span>
                            <span class="font-weight-bold ml-1"><?php echo htmlspecialchars($rec['chief_complaint'] ?? ''); ?></span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="p-2 border rounded h-100 bg-white">
                                <strong class="text-dark d-block mb-1"><i class="fas fa-notes-medical text-muted"></i> Triệu chứng:</strong>
                                <p class="text-secondary small mb-0">
                                    <?php echo nl2br(htmlspecialchars($rec['symptoms'] ?? '')); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="p-2 border rounded h-100 bg-white" style="border-left: 3px solid #e74a3b !important;">
                                <strong class="text-danger d-block mb-1"><i class="fas fa-diagnoses"></i> Chẩn đoán:</strong>
                                <p class="text-dark font-weight-bold small mb-0">
                                    <?php echo nl2br(htmlspecialchars($rec['diagnosis'] ?? '')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-1">
                        <div class="col-md-6">
                            <div class="alert alert-success mb-0 p-2 small h-100">
                                <strong class="d-block"><i class="fas fa-pills"></i> Đơn thuốc:</strong>
                                <?php echo nl2br(htmlspecialchars($rec['prescription'] ?? 'Không có đơn thuốc')); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0 p-2 small h-100">
                                <strong class="d-block"><i class="fas fa-user-md"></i> Hướng điều trị & Dặn dò:</strong>
                                <?php echo nl2br(htmlspecialchars($rec['treatment_plan'] ?? '')); ?>
                                <?php if(!empty($rec['notes'])) echo "<hr class='my-1'><em>Ghi chú: " . htmlspecialchars($rec['notes']) . "</em>"; ?>
                            </div>
                        </div>
                    </div>

                    <?php if($rec['follow_up_date']): ?>
                        <div class="mt-2 text-center">
                            <span class="badge badge-warning text-dark border border-warning px-3 py-1">
                                <i class="fas fa-calendar-check"></i> Hẹn tái khám: <strong><?php echo date('d/m/Y', strtotime($rec['follow_up_date'])); ?></strong>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="fas fa-folder-open fa-3x mb-3 text-gray-300"></i>
        <p>Bệnh nhân này chưa có hồ sơ bệnh án nào.</p>
        <button class="btn btn-outline-primary btn-sm" onclick="openAddRecordModal(<?php echo $pid; ?>)">Tạo hồ sơ đầu tiên</button>
    </div>
<?php endif; ?>