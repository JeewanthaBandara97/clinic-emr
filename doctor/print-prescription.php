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
        /* Print Styles */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            background: #f5f5f5;
        }
        
        .prescription-wrapper {
            max-width: 210mm;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .prescription-page {
            padding: 20mm;
            min-height: 297mm;
            position: relative;
        }
        
        /* Header */
        .prescription-header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .clinic-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        
        .clinic-info {
            font-size: 10pt;
            color: #666;
        }
        
        .doctor-info {
            text-align: right;
        }
        
        .doctor-name {
            font-size: 14pt;
            font-weight: 600;
            color: #333;
        }
        
        /* Patient Info */
        .patient-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .patient-section table {
            width: 100%;
            font-size: 11pt;
        }
        
        .patient-section td {
            padding: 3px 10px 3px 0;
        }
        
        .patient-section .label {
            font-weight: 600;
            color: #555;
            width: 100px;
        }
        
        /* Diagnosis Section */
        .diagnosis-section {
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .section-title {
            font-weight: 600;
            color: #2563eb;
            font-size: 11pt;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        /* Rx Symbol */
        .rx-symbol {
            font-size: 36pt;
            font-weight: bold;
            color: #2563eb;
            font-style: italic;
            margin-bottom: 10px;
        }
        
        /* Medicine Table */
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .medicine-table th {
            background: #2563eb;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10pt;
            font-weight: 600;
        }
        
        .medicine-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .medicine-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .medicine-name {
            font-weight: 600;
            color: #333;
        }
        
        .medicine-details {
            font-size: 10pt;
            color: #666;
        }
        
        /* Tests Section */
        .tests-section {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .tests-section .section-title {
            color: #856404;
        }
        
        .test-item {
            display: inline-block;
            background: white;
            padding: 5px 12px;
            margin: 3px;
            border-radius: 20px;
            font-size: 10pt;
            border: 1px solid #ffc107;
        }
        
        /* Footer */
        .prescription-footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            border-top: 2px solid #2563eb;
            padding-top: 15px;
        }
        
        .signature-area {
            float: right;
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 10pt;
        }
        
        /* Print Button */
        .print-controls {
            text-align: center;
            padding: 20px;
            background: #333;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .prescription-wrapper {
                box-shadow: none;
                margin: 0;
            }
            
            .print-controls {
                display: none !important;
            }
            
            .prescription-page {
                padding: 0;
            }
        }
        
        /* Vital Signs */
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .vital-item {
            background: #e7f1ff;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .vital-value {
            font-size: 16pt;
            font-weight: 600;
            color: #2563eb;
        }
        
        .vital-label {
            font-size: 9pt;
            color: #666;
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
                            Medical Practitioner<br>
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
                            <div class="vital-value"><?php echo $vitals['temperature']; ?>°C</div>
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
            
            <!-- Diagnosis -->
            <?php if ($prescription['diagnosis']): ?>
                <div class="diagnosis-section">
                    <div class="section-title"><i class="bi bi-clipboard2-pulse me-1"></i>Diagnosis</div>
                    <p style="margin:0;"><?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Prescription Medicines -->
            <?php if (!empty($prescription['medicines'])): ?>
                <div class="rx-symbol">℞</div>
                
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
                                    <?php if ($medicine['route'] && $medicine['route'] !== 'Oral'): ?>
                                        <br><span class="medicine-details">(<?php echo $medicine['route']; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($medicine['dose']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['frequency']); ?></td>
                                <td><?php echo $medicine['duration_days']; ?> days</td>
                                <td class="medicine-details"><?php echo htmlspecialchars($medicine['instructions']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Tests Requested -->
            <?php if (!empty($prescription['tests'])): ?>
                <div class="tests-section">
                    <div class="section-title"><i class="bi bi-clipboard-check me-1"></i>Tests Requested</div>
                    <?php foreach ($prescription['tests'] as $test): ?>
                        <span class="test-item">
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            <?php echo htmlspecialchars($test['test_name']); ?>
                            <?php if ($test['urgency'] !== 'Routine'): ?>
                                <span class="badge bg-danger"><?php echo $test['urgency']; ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Notes -->
            <?php if ($prescription['notes']): ?>
                <div class="mb-4">
                    <div class="section-title"><i class="bi bi-info-circle me-1"></i>Notes & Advice</div>
                    <p><?php echo nl2br(htmlspecialchars($prescription['notes'])); ?></p>
                </div>
            <?php endif; ?>
            
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>