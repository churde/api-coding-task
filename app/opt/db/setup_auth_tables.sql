-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Create permissions table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Create role_permissions table
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Create user_roles table
CREATE TABLE user_roles (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Insert some initial data
INSERT INTO roles (name) VALUES ('admin'), ('editor'), ('viewer');
INSERT INTO permissions (name) VALUES ('read'), ('create'), ('update'), ('delete');

-- Assign permissions to roles
INSERT INTO role_permissions (role_id, permission_id) 
SELECT r.id, p.id
FROM roles r, permissions p
WHERE 
    (r.name = 'admin') OR
    (r.name = 'editor' AND p.name IN ('read', 'create', 'update')) OR
    (r.name = 'viewer' AND p.name = 'read');

-- Create test users (password is 'password' - remember to use proper password hashing in production)
INSERT INTO users (username, password) VALUES 
('admin', 'password'),
('editor', 'password'),
('viewer', 'password');

-- Assign roles to test users
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE (u.username = 'admin' AND r.name = 'admin')
   OR (u.username = 'editor' AND r.name = 'editor')
   OR (u.username = 'viewer' AND r.name = 'viewer');
