-- Phase 8A: Database Migration Script
-- Run this to add missing columns to existing database

-- Step 1: Add missing columns to requests table
ALTER TABLE requests ADD COLUMN user_id INT DEFAULT 1 AFTER disaster_id;
ALTER TABLE requests ADD COLUMN approval_status VARCHAR(50) DEFAULT 'pending' AFTER status;
ALTER TABLE requests ADD COLUMN approved_by INT AFTER approval_status;
ALTER TABLE requests ADD COLUMN approval_date DATETIME AFTER approved_by;

-- Step 2: Add missing columns to allocations table
ALTER TABLE allocations ADD COLUMN fulfilled_by INT AFTER delivery_status;

-- Step 3: Add foreign key constraints
ALTER TABLE requests ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE requests ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE allocations ADD FOREIGN KEY (fulfilled_by) REFERENCES users(id) ON DELETE SET NULL;

-- Step 4: Update existing sample data to link requests to worker user
UPDATE requests SET user_id = 2 WHERE id > 0;  -- Link all to user1 (worker)

-- Verification queries (run after migration)
-- SELECT * FROM requests;  -- Should show user_id, approval_status, approved_by
-- SELECT * FROM allocations;  -- Should show fulfilled_by
