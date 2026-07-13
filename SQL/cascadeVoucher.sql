-- Step 1: Drop existing foreign key if needed
ALTER TABLE member_vouchers
DROP FOREIGN KEY member_vouchers_ibfk_1;

-- Step 2: Add new foreign key with ON DELETE CASCADE
ALTER TABLE member_vouchers
ADD CONSTRAINT fk_voucherID
FOREIGN KEY (voucherID)
REFERENCES vouchers(voucherID)
ON DELETE CASCADE;
