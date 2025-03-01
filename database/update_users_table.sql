-- Add bio and avatar columns to users table
ALTER TABLE users
ADD COLUMN bio TEXT DEFAULT NULL,
ADD COLUMN avatar VARCHAR(255) DEFAULT NULL; 