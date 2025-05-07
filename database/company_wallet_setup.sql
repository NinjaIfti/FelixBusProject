-- FelixBus Company Wallet Setup Script

-- Create FelixBus company user
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
VALUES ('felixbus', '$2y$10$EldNVCYsBJwFvJx9fRJBE.8/NNZQ.y/qbXrwh66qs8JhStFE6Y7fW', 'admin@felixbus.com', 'FelixBus', 'Company', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Get the FelixBus user ID
SET @company_id = (SELECT id FROM users WHERE username = 'felixbus');

-- Create wallet for FelixBus company
INSERT INTO wallets (user_id, balance) 
VALUES (@company_id, 0.00)
ON DUPLICATE KEY UPDATE id=id;

-- Add an initial transaction to record the setup
INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
SELECT id, 0.00, 'system', 'Company wallet initialization'
FROM wallets
WHERE user_id = @company_id;

-- Display confirmation message
SELECT 'FelixBus company wallet has been successfully set up!' AS Message; 