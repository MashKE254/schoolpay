# Bloomsfield Kindergarten & School - SchoolPay Setup Guide

## Overview

This guide will help you set up SchoolPay for Bloomsfield Kindergarten & School with full support for:

- ✅ **Transport Zones** - 8 zones with round-trip and one-way pricing
- ✅ **One-Time Fees** - Admission, Diary, Pouch, Covers
- ✅ **Annual Fees** - Personal Accident Insurance
- ✅ **Extra-Curricular Activities** - Skating, Ballet, Swimming, etc.
- ✅ **Recurring Fees** - Tuition, Lunch & Break, Sports/Swimming

---

## Step 1: Run Database Migration

Before using the new features, you need to create the necessary database tables.

### Option A: Via PHP CLI
```bash
cd /path/to/schoolpay
php run_bloomsfield_migration.php
# When prompted, enter your school_id (usually 1 for the first school)
```

### Option B: Via Browser
```
https://your-domain.com/schoolpay/run_bloomsfield_migration.php?token=migrate_bloomsfield_2026&school_id=1
```

### Option C: Via MySQL Import
```bash
# First, edit migration_bloomsfield_features.sql
# Replace {SCHOOL_ID} with your actual school_id (e.g., 1)
mysql -u root -p schoolpay < migration_bloomsfield_features.sql
```

**Expected Result:**
- ✅ 6 new tables created
- ✅ 8 transport zones created
- ✅ 11 activities created
- ✅ `items` table updated with `fee_frequency` column

---

## Step 2: Configure Fee Items

### 2.1 Mark Existing Fee Items by Type

Go to **Customer Center → Fee Structure → Manage Items**

Update each item's `fee_frequency`:

| Fee Item | Fee Frequency | Amount |
|----------|---------------|--------|
| Tuition | `recurring` | Varies by class |
| Lunch & Break | `recurring` | Varies by class |
| Sports/Swimming | `recurring` | 3,000 (optional for higher grades) |
| Admission Fee | `one_time` | 5,000 |
| Diary (BB, Beginner) | `one_time` | 500 |
| Diary (PP1-GR3) | `one_time` | 400 |
| Pouch | `one_time` | 400 |
| Covers | `one_time` | 400 |
| Personal Accident Insurance | `annual` | 1,500 |

### 2.2 Create Fee Structure Per Class

Upload via CSV or manually assign fees per class:

**PLAYGROUP (2-3YRS)**
- Tuition: 26,000 (mandatory)
- Lunch & Break: 9,000 (mandatory)
- Sports/Swimming/Kinder Music: 3,000 (optional)

**BEGINNER (3-4YRS)**
- Tuition: 32,500 (mandatory)
- Lunch & Break: 9,000 (mandatory)
- Sports/Swimming/Kinder Music: 3,000 (optional)

**PP1 (4-5YRS)**
- Tuition: 36,000 (mandatory)
- Lunch & Break: 10,000 (mandatory)
- Sports/Swimming: N/A (use Activities instead)

**PP2 (5-6YRS)**
- Tuition: 37,000 (mandatory)
- Lunch & Break: 10,000 (mandatory)

**GR-1, GR-2, GR-3**
- Tuition: 38,500 (mandatory)
- Lunch & Break: 10,000 (mandatory)

---

## Step 3: Configure Transport Zones

The migration already created all 8 zones. Verify them:

Navigate to: **Transport Management → Transport Zones**

You should see:

| Zone | Areas | Round Trip | One Way |
|------|-------|------------|---------|
| ZONE-1 | Delta, Ruaka town, Joyland | 10,000 | 7,000 |
| ZONE-2 | Mifereji, Kahigo, Guango, Kigwaru | 11,500 | 9,000 |
| ZONE-3 | Mucatha, Gacharage, Sacred heart, Ndenderu | 14,000 | 12,500 |
| ZONE-4 | Runda, Gigiri, Banana, Karura, Gachie | 15,500 | 13,000 |
| ZONE-5 | Kiambu, Marurul, Kihara, Laini Ridgeways | 19,500 | 17,500 |
| ZONE-6 | Redhill, Nazareth, Windsor | 22,500 | 18,500 |
| ZONE-7 | Lower Kabete, Mwimuto, Kitsuru | 24,500 | 21,500 |
| ZONE-8 | Poster Kabuku, Kiambu town | 26,500 | 23,000 |

**To edit or add zones:** Click "Edit" or "Add New Zone"

---

## Step 4: Configure Activities

The migration already created all activities. Verify them:

Navigate to: **Activities Management → Activities**

You should see:

| Activity | Category | Fee per Term |
|----------|----------|--------------|
| Skating | Sports | 5,000 |
| Ballet | Arts | 5,000 |
| Taekwondo | Sports | 5,000 |
| Chess | Games | 5,000 |
| Music (Piano, Guitar, Drums, Violin) | Music | 5,000 |
| Languages (Chinese or French) | Languages | 5,000 |
| Basketball | Sports | 5,000 |
| Gymnastics | Sports | 5,000 |
| Swimming | Sports | 5,000 |
| Sports (Basketball, badminton, Dance) | Sports | 5,000 |
| Abacus (Grade 1-3) | Academic | 2,000 |

**To edit or add activities:** Click "Edit" or "Add New Activity"

---

## Step 5: Enroll Students in Transport & Activities

### 5.1 Assign Student to Transport Zone

**Option A: Individual Assignment**
1. Go to: **Transport Management → Student Assignments**
2. Click "Assign Student to Transport"
3. Select:
   - Student
   - Transport Zone
   - Trip Type (Round Trip or One Way)
   - Academic Year & Term
4. Click "Assign Transport"

**Option B: Bulk Assignment via CSV**
(Coming soon - for now use individual assignment)

### 5.2 Enroll Students in Activities

**Option A: Individual Enrollment**
1. Go to: **Activities Management → Student Enrollments**
2. Click "Enroll Student"
3. Select:
   - Student
   - Activity
   - Academic Year & Term
4. Click "Enroll Student"

**Option B: Bulk Enroll Entire Class**
1. Go to: **Activities Management → Student Enrollments**
2. Click "Bulk Enroll Class"
3. Select:
   - Class (e.g., PP1)
   - Activity (e.g., Swimming)
   - Academic Year & Term
4. Click "Enroll Entire Class"

---

## Step 6: Generate Invoices

### 6.1 Single Student Invoice

1. Go to: **Customer Center → Create Invoice** or **Create Invoice** (main menu)
2. Fill in:
   - Academic Year: `2026-2027`
   - Term: `Term 1`
   - Bill To: Select student
   - Invoice Date & Due Date
3. The system will **automatically load**:
   - ✅ Mandatory fees for their class (Tuition, Lunch)
   - ✅ Transport fee (if assigned)
   - ✅ Their enrolled activities
   - ✅ One-time fees (if not already billed)
   - ✅ Annual fees (if not billed this year)
4. Optional fees will appear in a separate section
5. Click "Create Invoice"

### 6.2 Bulk Invoicing for Entire Class

1. Go to: **Customer Center → Create Invoice**
2. Select "Entire Class" radio button
3. Select Class, Academic Year, Term
4. The system will:
   - Create individual invoices for all students in the class
   - Include each student's specific transport zone fees
   - Include each student's enrolled activities
   - Apply one-time/annual fee rules automatically

---

## Step 7: Understanding Fee Frequencies

### Recurring Fees (Every Term)
- Charged **every term**
- Example: Tuition, Lunch & Break
- Always appear on invoices

### One-Time Fees (Once per Student)
- Charged **only once** in the student's lifetime
- Example: Admission Fee, Diary, Pouch, Covers
- After first invoice, will NOT appear again
- Tracked in `one_time_fees_billed` table

### Annual Fees (Once per Academic Year)
- Charged **once per academic year**
- Example: Personal Accident Insurance (1,500 KSH)
- Will appear in Term 1 invoice
- Will NOT appear in Term 2 or Term 3 of the same year
- Tracked in `annual_fees_billed` table

---

## Step 8: Sample Fee Calculation

**Example Student:** John Doe, PP1, ZONE-2 Transport (Round Trip), Enrolled in Swimming & Chess

### Term 1 Invoice (New Student)
- Tuition (PP1): 36,000
- Lunch & Break (PP1): 10,000
- Transport ZONE-2 (Round Trip): 11,500
- Activity: Swimming: 5,000
- Activity: Chess: 5,000
- **One-Time Fees (New Student)**:
  - Admission Fee: 5,000
  - Diary (PP1): 400
  - Pouch: 400
  - Covers: 400
- **Annual Fees**:
  - Personal Accident Insurance: 1,500

**TOTAL TERM 1:** 75,200 KSH

### Term 2 Invoice (Same Year)
- Tuition (PP1): 36,000
- Lunch & Break (PP1): 10,000
- Transport ZONE-2 (Round Trip): 11,500
- Activity: Swimming: 5,000
- Activity: Chess: 5,000

**TOTAL TERM 2:** 67,500 KSH
(No one-time fees, no annual fees - already billed)

### Term 1 Next Year (2027-2028)
- Tuition (PP2): 37,000 (promoted)
- Lunch & Break (PP2): 10,000
- Transport ZONE-3 (Round Trip): 14,000 (moved to new zone)
- Activity: Swimming: 5,000
- **Annual Fee**:
  - Personal Accident Insurance: 1,500 (new year)

**TOTAL TERM 1 (Year 2):** 67,500 KSH
(No one-time fees - already billed for lifetime)

---

## Step 9: Reports & Tracking

### 9.1 Transport Report
Navigate to: **Transport Management → Student Assignments**

View all students assigned to transport zones with amounts.

### 9.2 Activities Report
Navigate to: **Activities Management → Student Enrollments**

View all students enrolled in activities.

### 9.3 Fee Billing History
**One-Time Fees:**
```sql
SELECT s.name, i.name as item_name, otfb.billed_date, otfb.amount
FROM one_time_fees_billed otfb
JOIN students s ON otfb.student_id = s.id
JOIN items i ON otfb.item_id = i.id
WHERE otfb.school_id = 1;
```

**Annual Fees:**
```sql
SELECT s.name, i.name as item_name, afb.academic_year, afb.billed_date, afb.amount
FROM annual_fees_billed afb
JOIN students s ON afb.student_id = s.id
JOIN items i ON afb.item_id = i.id
WHERE afb.school_id = 1
ORDER BY afb.academic_year DESC;
```

---

## Step 10: Common Tasks

### Change Student's Transport Zone
1. Go to: **Transport Management → Student Assignments**
2. Find student in list
3. Click "Remove" to delete old assignment
4. Click "Assign Student to Transport"
5. Select new zone and save

**Note:** This only affects future invoices. Past invoices remain unchanged.

### Unenroll Student from Activity
1. Go to: **Activities Management → Student Enrollments**
2. Find student's activity enrollment
3. Click "Remove" button

**Note:** This only affects future invoices. Past invoices remain unchanged.

### Correct Wrongly Billed One-Time Fee
If you accidentally billed a one-time fee twice:

1. Delete the invoice (if not paid)
2. Or: Manually delete from `one_time_fees_billed` table:
```sql
DELETE FROM one_time_fees_billed
WHERE student_id = X AND item_id = Y;
```

---

## Troubleshooting

### Issue: Transport fees not appearing on invoice
**Solution:**
- Verify student has active transport assignment for that academic year + term
- Check Transport Management → Student Assignments
- Ensure transport zone status is "active"

### Issue: Activity fees not appearing
**Solution:**
- Verify student is enrolled in activity for that academic year + term
- Check Activities Management → Student Enrollments
- Ensure activity status is "active"

### Issue: One-time fee appearing again
**Solution:**
- Check `one_time_fees_billed` table - should have entry
- If missing, it means it wasn't tracked on first invoice
- Manually add tracking record to prevent future billing

### Issue: Annual fee appearing every term
**Solution:**
- Check item `fee_frequency` is set to `annual` not `recurring`
- Check `annual_fees_billed` table for existing record

---

## Support & Contact

For technical support:
- Email: support@schoolpay.com
- Phone: 0713794843 / 0726730582

For Bloomsfield-specific setup:
- Contact: School Administrator
- Bank: CO-OP BANK, RIDGEWAYS BRANCH
- Account: 01191756258000
- PayBill: 727455

---

## Appendix A: CSV Upload Templates

### Transport Assignments CSV
```csv
student_name,transport_zone,trip_type,academic_year,term
John Doe,ZONE-1,round_trip,2026-2027,Term 1
Jane Smith,ZONE-2,one_way,2026-2027,Term 1
```

### Activity Enrollments CSV
```csv
student_name,activity_name,academic_year,term
John Doe,Swimming,2026-2027,Term 1
John Doe,Chess,2026-2027,Term 1
Jane Smith,Ballet,2026-2027,Term 1
```

---

## Appendix B: Database Table Reference

### New Tables Created

1. **transport_zones** - Master list of transport zones
2. **student_transport** - Student transport assignments
3. **activities** - Master list of extra-curricular activities
4. **student_activities** - Student activity enrollments
5. **one_time_fees_billed** - Tracks one-time fees per student
6. **annual_fees_billed** - Tracks annual fees per student per year

### Updated Tables

- **items** - Added `fee_frequency` and `applies_to` columns

---

**Last Updated:** January 2026
**Version:** 1.0
**For:** Bloomsfield Kindergarten & School
