# AMENDED PRESCRIPTION FEATURE - IMPLEMENTATION GUIDE

## What's Been Created

Three new files have been created to enable amended prescriptions:

### 1. **visit-prescriptions.php** - View all prescriptions for a visit
- Shows original prescription + all amendments
- Color-coded badges distinguish originals from amendments
- Quick "Print" and "Amend" buttons

### 2. **amend-prescription.php** - Create amended prescription
- Shows the original prescription medicines for reference
- Let's doctor add/modify medicines for the amendment
- Creates a NEW linked prescription (not modifying the original)
- Maintains full audit trail

### 3. **Migration Script** - Database update
- Adds `parent_prescription_id` column to track amendments
- Enables linking of amended prescriptions to originals

## How It Works

### Scenario: Doctor needs to amend a prescription after patient consulting

**Step 1: View Patient History**
- Go to Patient History
- Click the small **"Rx"** button next to any completed visit
- This shows all prescriptions for that visit (originals + amendments)

**Step 2: Create Amendment**
- On the prescription view page, click **"Amend"** button
- The original medicines are shown for reference
- Doctor can add/modify medicines in the "Amended Medicines" section
- Can add notes explaining what changed and why
- Click **"Create Amendment & Print"**

**Step 3: Audit Trail**
- Original prescription stays intact (status = "Active")
- NEW amended prescription is created (marked as "AMENDED")
- Patient gets new amended prescription to follow
- Both versions are in the system for complete history

## Setup Instructions

### 1. Update Database
Run this SQL script in phpMyAdmin:

```sql
ALTER TABLE `prescriptions` 
ADD COLUMN `parent_prescription_id` INT UNSIGNED NULL AFTER `prescription_id`,
ADD CONSTRAINT `fk_prescriptions_parent` 
  FOREIGN KEY (`parent_prescription_id`) 
  REFERENCES `prescriptions`(`prescription_id`) 
  ON DELETE SET NULL;

CREATE INDEX `idx_parent_prescription_id` ON `prescriptions`(`parent_prescription_id`);
```

**OR** use the provided migration file:
- File: `database/migrations/001_add_amendment_tracking.sql`

### 2. Files Already Created
✅ `/doctor/visit-prescriptions.php` - List prescriptions for a visit
✅ `/doctor/amend-prescription.php` - Create amended prescription  
✅ `/doctor/patient-history.php` - Updated with Rx button

### 3. Verify Links
The workflow is:
1. Patient History → Click "Rx" button on visit → View Prescriptions page
2. View Prescriptions page → Click "Amend" → Amendment page
3. Amendment page → Create & Print new amended prescription

## User Workflow

```
Patient History (Shows visits)
        ↓
[Click Rx button on visit]
        ↓
Visit Prescriptions (Shows original + any amendments)
        ↓
[Click "Amend" on prescription]
        ↓
Amend Prescription (Create new amended version)
        ↓
[Submit] → Redirects to Print
        ↓
Print Amended Prescription
```

## Key Features

✅ **Non-destructive**: Original prescription never modified
✅ **Audit Trail**: Both original and amendments visible in history
✅ **Legal Safe**: Clear separation of original vs amended versions
✅ **Easy to Use**: Simple button-to-button workflow
✅ **Multiple Amendments**: Can amend multiple times if needed
✅ **Linked Records**: Amendments linked to original via `parent_prescription_id`

## Database Changes Summary

### New Column: `parent_prescription_id`
- **Added to**: `prescriptions` table
- **Type**: INT UNSIGNED, NULL
- **Purpose**: Links amended prescriptions to their originals
- **When set**: Only on amended prescriptions (originals have NULL)
- **Index**: `idx_parent_prescription_id` for fast lookups

### Example Data

**Original Prescription:**
```
prescription_id: 1
prescription_code: RX20260308001
parent_prescription_id: NULL  ← Original (no parent)
status: Active
```

**Amended Prescription:**
```
prescription_id: 2
prescription_code: RX20260308002
parent_prescription_id: 1    ← Links to original
status: Active
```

## Testing the Feature

1. Create a visit and save it
2. Complete the visit with prescription
3. Go to Patient History → Click "Rx" button on the visit
4. Should see the prescription with "ORIGINAL" badge
5. Click "Amend" button
6. Modify medicines and click "Create Amendment & Print"
7. Should be redirected to print the amended prescription
8. Go back to prescriptions view - should now see both original and amendment

## Troubleshooting

**If "Rx" button doesn't appear:**
- Make sure visit status is "Completed"
- The button is in the right-hand column of each visit

**If "Amend" button missing:**
- Only shows for prescriptions with status = "Active"
- Check prescription status in database

**If script says column already exists:**
- The database column is already there, no need to run migration again

## Future Enhancements

- Email old + new prescription to patient
- Require reason for amendment
- Notification to pharmacy about amendments
- Amendment review workflow (require approval before printing)
- Compare original vs amended medicines side-by-side
