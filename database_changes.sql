-- Add admission_no column to customers table
ALTER TABLE customers ADD COLUMN admission_no VARCHAR(20) NOT NULL UNIQUE AFTER id;

-- Drop existing foreign key constraints if they exist
ALTER TABLE invoices DROP FOREIGN KEY IF EXISTS invoices_ibfk_1;
ALTER TABLE invoices DROP FOREIGN KEY IF EXISTS fk_invoice_student;

-- Drop admission_no column if it exists
ALTER TABLE invoices DROP COLUMN IF EXISTS admission_no;

-- Add student_id column to invoices table if it doesn't exist
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS student_id INT NOT NULL AFTER id;

-- Add foreign key constraint for student_id
ALTER TABLE invoices ADD CONSTRAINT fk_invoice_student 
FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE;

-- Add notes column to invoices table if it doesn't exist
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add parent_id and item_type columns to items table if they don't exist
ALTER TABLE items ADD COLUMN IF NOT EXISTS parent_id INT NULL;
ALTER TABLE items ADD COLUMN IF NOT EXISTS item_type ENUM('parent', 'child') DEFAULT 'parent';

-- Add foreign key constraint for parent_id
ALTER TABLE items ADD CONSTRAINT fk_parent_item FOREIGN KEY (parent_id) REFERENCES items(id);

-- Add index for parent_id
CREATE INDEX IF NOT EXISTS idx_parent_id ON items(parent_id);

-- Create invoice_items table if it doesn't exist
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Insert some test customers with admission numbers
INSERT INTO customers (name, email, phone, address, admission_no) VALUES
('John Doe', 'john@example.com', '1234567890', '123 Main St', 'ADM001'),
('Jane Smith', 'jane@example.com', '0987654321', '456 Oak Ave', 'ADM002'),
('Bob Johnson', 'bob@example.com', '5551234567', '789 Pine Rd', 'ADM003');

-- Create chart of accounts table
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) NOT NULL UNIQUE,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create journal entries table
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    debit_account INT NOT NULL,
    credit_account INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (debit_account) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (credit_account) REFERENCES chart_of_accounts(id)
);

-- Insert default chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type) VALUES
('1000', 'Cash', 'Asset'),
('1100', 'Accounts Receivable', 'Asset'),
('1200', 'Inventory', 'Asset'),
('2000', 'Accounts Payable', 'Liability'),
('3000', 'Owner''s Equity', 'Equity'),
('4000', 'Sales Revenue', 'Revenue'),
('5000', 'Cost of Goods Sold', 'Expense'),
('5100', 'Salaries Expense', 'Expense'),
('5200', 'Rent Expense', 'Expense'),
('5300', 'Utilities Expense', 'Expense'); 