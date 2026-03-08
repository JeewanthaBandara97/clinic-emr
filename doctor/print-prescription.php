<?php
/**
 * Print Prescription
 * Clinic EMR System
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Prescription.php';
require_once __DIR__ . '/../classes/Test.php';
require_once __DIR__ . '/../classes/Visit.php';

$prescriptionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$prescriptionId) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Invalid prescription ID.');
}

$prescriptionObj = new Prescription();
$prescription = $prescriptionObj->getFullPrescription($prescriptionId);

if (!$prescription) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Prescription not found.');
}

$pageTitle = 'Prescription - ' . $prescription['prescription_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.35;
            background: #f5f5f5;
            color: #111;
        }
        .prescription-wrapper {
            max-width: 210mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
        }
        .prescription-page {
            padding: 12mm;
            min-height: 277mm;
            position: relative;
        }
        .prescription-page + .prescription-page {
            margin-top: 16px;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }
        .prescription-header {
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .clinic-name {
            font-size: 21pt;
            font-weight: 700;
            color: #111;
            margin: 0;
            line-height: 1.1;
        }
        .clinic-info {
            font-size: 9.5pt;
            color: #333;
        }
        .doctor-info {
            text-align: right;
        }
        .doctor-name {
            font-size: 12pt;
            font-weight: 600;
            color: #111;
        }
        .patient-section {
            border: 1px solid #cfcfcf;
            border-radius: 0;
            padding: 10px;
            margin-bottom: 12px;
        }
        .patient-section table {
            width: 100%;
            font-size: 10pt;
        }
        .patient-section td {
            padding: 2px 8px 2px 0;
        }
        .patient-section .label {
            font-weight: 600;
            color: #222;
            width: 100px;
        }
        .diagnosis-section {
            margin-bottom: 12px;
            padding: 6px 0;
            border-bottom: 1px solid #cfcfcf;
        }
        .section-title {
            font-weight: 700;
            color: #111;
            font-size: 10pt;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .medicine-table th {
            background: #fff;
            color: #111;
            padding: 7px;
            text-align: left;
            font-size: 9.5pt;
            font-weight: 700;
            border-top: 1px solid #777;
            border-bottom: 1px solid #777;
        }
        .medicine-table td {
            padding: 7px;
            border-bottom: 1px solid #d7d7d7;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .medicine-name {
            font-weight: 600;
            color: #111;
        }
        .medicine-details {
            font-size: 9pt;
            color: #333;
        }
        .tests-section {
            border: 1px solid #cfcfcf;
            border-radius: 0;
            padding: 10px;
            margin-bottom: 12px;
        }
        .tests-section .section-title {
            color: #111;
        }
        .test-item {
            display: inline-block;
            padding: 4px 10px;
            margin: 3px;
            border-radius: 0;
            font-size: 9.5pt;
            border: 1px solid #bdbdbd;
        }
        .prescription-footer {
            border-top: 1px solid #777;
            margin-top: 14px;
            padding-top: 8px;
            page-break-inside: avoid;
        }
        .signature-area {
            float: right;
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10pt;
        }
        .print-controls {
            text-align: center;
            padding: 20px;
            background: #333;
        }
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .vital-item {
            border: 1px solid #cfcfcf;
            padding: 8px 6px;
            border-radius: 0;
            text-align: center;
        }
        .vital-value {
            font-size: 13pt;
            font-weight: 600;
            color: #111;
        }
        .vital-label {
            font-size: 8.5pt;
            color: #333;
        }
        @media print {
            body {
                background: #fff;
                color: #000;
            }
            .prescription-wrapper {
                box-shadow: none;
                margin: 0;
                max-width: none;
            }
            .print-controls,
            .bi {
                display: none !important;
            }
            .prescription-page {
                padding: 0;
                min-height: auto;
            }
            .prescription-page + .prescription-page {
                margin-top: 0;
            }
            .badge {
                border: 1px solid #666 !important;
                color: #111 !important;
                background: #fff !important;
            }
            .row,
            .col-6,
            .col-5,
            .col-7,
            .medicine-table,
            .tests-section,
            .patient-section,
            .diagnosis-section,
            .vitals-grid,
            .prescription-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            a,
            a:visited {
                color: #000 !important;
                text-decoration: none !important;
            }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                page-break-before: always;
                break-before: page;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
            <i class="bi bi-printer me-2"></i>Print Prescription
        </button>
        <a href="patient-queue.php" class="btn btn-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Back to Queue
        </a>
    </div>
    
    <div class="prescription-wrapper">
        <!-- Page 1: Clinical Summary -->
        <div class="prescription-page">
            <!-- Header -->
            <div class="prescription-header">
                <div class="row align-items-center">
                    <div class="col-7">
                        <h1 class="clinic-name"><?php echo CLINIC_NAME; ?></h1>
                        <div class="clinic-info">
                            <i class="bi bi-geo-alt me-1"></i><?php echo CLINIC_ADDRESS; ?><br>
                            <i class="bi bi-telephone me-1"></i><?php echo CLINIC_PHONE; ?> | 
                            <i class="bi bi-envelope me-1"></i><?php echo CLINIC_EMAIL; ?>
                        </div>
                    </div>
                    <div class="col-5 doctor-info">
                        <div class="doctor-name"><?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
                        <div class="clinic-info">
                            <?php echo !empty($prescription['qualification']) ? htmlspecialchars($prescription['qualification']) : 'Medical Practitioner'; ?><br>
                            Date: <?php echo formatDate($prescription['prescription_date']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Patient Information -->
            <div class="patient-section">
                <div class="row">
                    <div class="col-6">
                        <table>
                            <tr>
                                <td class="label">Patient:</td>
                                <td><strong><?php echo htmlspecialchars($prescription['patient_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="label">Patient ID:</td>
                                <td><?php echo htmlspecialchars($prescription['patient_code']); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Age/Gender:</td>
                                <td><?php echo $prescription['patient_age']; ?> years / <?php echo $prescription['patient_gender']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <table>
                            <tr>
                                <td class="label">Rx No:</td>
                                <td><strong><?php echo htmlspecialchars($prescription['prescription_code']); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="label">Phone:</td>
                                <td><?php echo htmlspecialchars($prescription['patient_phone']); ?></td>
                            </tr>
                            <?php if ($prescription['allergies']): ?>
                                <tr>
                                    <td class="label" style="color:#dc3545;">Allergies:</td>
                                    <td style="color:#dc3545;"><?php echo htmlspecialchars($prescription['allergies']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Vital Signs -->
            <?php if ($prescription['vital_signs']): ?>
                <?php $vitals = $prescription['vital_signs']; ?>
                <div class="vitals-grid">
                    <?php if ($vitals['blood_pressure_systolic'] && $vitals['blood_pressure_diastolic']): ?>
                        <div class="vital-item">
                            <div class="vital-value"><?php echo $vitals['blood_pressure_systolic']; ?>/<?php echo $vitals['blood_pressure_diastolic']; ?></div>
                            <div class="vital-label">Blood Pressure (mmHg)</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($vitals['pulse_rate']): ?>
                        <div class="vital-item">
                            <div class="vital-value"><?php echo $vitals['pulse_rate']; ?></div>
                            <div class="vital-label">Pulse Rate (bpm)</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($vitals['temperature']): ?>
                        <div class="vital-item">
                            <div class="vital-value"><?php echo $vitals['temperature']; ?> &deg;C</div>
                            <div class="vital-label">Temperature</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($vitals['weight']): ?>
                        <div class="vital-item">
                            <div class="vital-value"><?php echo $vitals['weight']; ?> kg</div>
                            <div class="vital-label">Weight</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Symptoms / Chief Complaint -->
            <?php if (!empty($prescription['symptoms'])): ?>
                <div class="diagnosis-section">
                    <div class="section-title"><i class="bi bi-bug me-1"></i>Symptoms / Chief Complaint</div>
                    <p style="margin:0;"><?php echo nl2br(htmlspecialchars($prescription['symptoms'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Diagnosis -->
            <?php if ($prescription['diagnosis']): ?>
                <div class="diagnosis-section">
                    <div class="section-title"><i class="bi bi-clipboard2-pulse me-1"></i>Diagnosis</div>
                    <p style="margin:0;"><?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Clinical Notes -->
            <?php if (!empty($prescription['clinical_notes'])): ?>
                <div class="mb-4">
                    <div class="section-title"><i class="bi bi-journal-medical me-1"></i>Clinical Notes</div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($prescription['clinical_notes'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Prescription Notes -->
            <?php if (!empty($prescription['notes'])): ?>
                <div class="mb-4">
                    <div class="section-title"><i class="bi bi-info-circle me-1"></i>Prescription Notes</div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($prescription['notes'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Follow-up Date -->
            <div class="diagnosis-section">
                <div class="section-title"><i class="bi bi-calendar-check me-1"></i>Follow-up Date</div>
                <p style="margin:0;">
                    <?php echo !empty($prescription['follow_up_date']) ? date('d M Y', strtotime($prescription['follow_up_date'])) : 'Not specified'; ?>
                </p>
            </div>
            
            <!-- Footer -->
            <div class="prescription-footer">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>Printed: <?php echo date('d M Y, h:i A'); ?><br>
                            <i class="bi bi-shield-check me-1"></i>This is a computer generated prescription
                        </small>
                    </div>
                    <div class="col-6">
                        <div class="signature-area">
                            <div class="signature-line">
                                <?php echo htmlspecialchars($prescription['doctor_name']); ?><br>
                                <small>Doctor's Signature</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page 2: Medicines and Tests -->
        <div class="prescription-page page-break">
            <div class="prescription-header">
                <div class="row align-items-center">
                    <div class="col-7">
                        <h1 class="clinic-name"><?php echo CLINIC_NAME; ?></h1>
                        <div class="clinic-info">
                            <?php echo CLINIC_ADDRESS; ?><br>
                            <?php echo CLINIC_PHONE; ?> | <?php echo CLINIC_EMAIL; ?>
                        </div>
                    </div>
                    <div class="col-5 doctor-info">
                        <div class="doctor-name"><?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
                        <div class="clinic-info">
                            <?php echo !empty($prescription['qualification']) ? htmlspecialchars($prescription['qualification']) : 'Medical Practitioner'; ?><br>
                            Rx: <?php echo htmlspecialchars($prescription['prescription_code']); ?><br>
                            Date: <?php echo formatDate($prescription['prescription_date']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="patient-section">
                <div class="row">
                    <div class="col-6">
                        <strong>Patient:</strong> <?php echo htmlspecialchars($prescription['patient_name']); ?>
                    </div>
                    <div class="col-6 text-end">
                        <strong>Patient ID:</strong> <?php echo htmlspecialchars($prescription['patient_code']); ?>
                    </div>
                </div>
            </div>

            <!-- Prescription Medicines -->
            <?php if (!empty($prescription['medicines'])): ?>
                <div class="section-title mb-2"><i class="bi bi-capsule me-1"></i>Medicines</div>
                <table class="medicine-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">Medicine</th>
                            <th width="15%">Dose</th>
                            <th width="20%">Frequency</th>
                            <th width="10%">Duration</th>
                            <th width="20%">Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescription['medicines'] as $index => $medicine): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="medicine-name"><?php echo htmlspecialchars($medicine['medicine_name']); ?></span>
                                    <?php if (!empty($medicine['route']) && $medicine['route'] !== 'Oral'): ?>
                                        <br><span class="medicine-details">(<?php echo htmlspecialchars($medicine['route']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($medicine['dose']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['frequency']); ?></td>
                                <td><?php echo (int)$medicine['duration_days']; ?> days</td>
                                <td class="medicine-details"><?php echo htmlspecialchars($medicine['instructions']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No medicines prescribed.</p>
            <?php endif; ?>

            <!-- Tests Requested -->
            <?php if (!empty($prescription['tests'])): ?>
                <div class="tests-section">
                    <div class="section-title"><i class="bi bi-clipboard-check me-1"></i>Tests Requested</div>
                    <?php foreach ($prescription['tests'] as $test): ?>
                        <span class="test-item">
                            <?php echo htmlspecialchars($test['test_name']); ?>
                            <?php if (!empty($test['urgency']) && $test['urgency'] !== 'Routine'): ?>
                                <span class="badge bg-danger"><?php echo htmlspecialchars($test['urgency']); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No tests requested.</p>
            <?php endif; ?>

            <div class="prescription-footer">
                <div class="row">
                    <div class="col-7">
                        <small class="text-muted d-block">
                            Printed: <?php echo date('d M Y, h:i A'); ?>
                        </small>
                    </div>
                    <div class="col-5">
                        <div class="signature-area">
                            <div class="signature-line">
                                <?php echo htmlspecialchars($prescription['doctor_name']); ?><br>
                                <small class="text-muted d-block mt-1">
                                    Specialization: <?php echo !empty($prescription['specialization']) ? htmlspecialchars($prescription['specialization']) : '-'; ?>
                                </small>
                                <small class="text-muted d-block">
                                    Qualification: <?php echo !empty($prescription['qualification']) ? htmlspecialchars($prescription['qualification']) : '-'; ?>
                                </small>
                                <small class="text-muted d-block">
                                    License No: <?php echo !empty($prescription['license_number']) ? htmlspecialchars($prescription['license_number']) : '-'; ?>
                                </small>
                                <small>Doctor's Signature</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
