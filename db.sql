CREATE TABLE
    IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        user_type ENUM ('admin', 'provider', 'customer') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- Provider profiles table
CREATE TABLE
    IF NOT EXISTS provider_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        business_name VARCHAR(100) NOT NULL,
        description TEXT,
        logo VARCHAR(255),
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        zip VARCHAR(20),
        website VARCHAR(255),
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Service categories table
CREATE TABLE
    IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- Services table
CREATE TABLE
    IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        category_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        price_range VARCHAR(50),
        availability TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (provider_id) REFERENCES provider_profiles (id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
    );

-- Service requests table
CREATE TABLE
    IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        customer_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status ENUM (
            'pending',
            'accepted',
            'rejected',
            'completed',
            'cancelled'
        ) DEFAULT 'pending',
        requested_date DATE,
        requested_time VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Notifications table
CREATE TABLE
    IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        related_id INT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- Insert default admin user
INSERT INTO
    users (username, email, password, user_type)
VALUES
    (
        'admin',
        'admin@example.com',
        '$2y$10$J28biXxZ3okP6K/zHFXsYuB6HE3o9OQsyzDXW5GXq7L08tZmpO7Da',
        'admin'
    );

-- Insert default service categories
INSERT INTO
    categories (name, description)
VALUES
    (
        'Home Services',
        'Plumbing, electrical, cleaning, repairs, etc.'
    ),
    (
        'Professional Services',
        'Legal, accounting, consulting, etc.'
    ),
    (
        'Health & Wellness',
        'Medical, fitness, therapy, etc.'
    ),
    (
        'Education & Tutoring',
        'Academic, skills, language, etc.'
    ),
    (
        'Technical Services',
        'IT support, web development, etc.'
    ),
    (
        'Beauty & Personal Care',
        'Hair, makeup, spa, etc.'
    ),
    (
        'Event Services',
        'Planning, catering, entertainment, etc.'
    ),
    (
        'Auto Services',
        'Repairs, maintenance, detailing, etc.'
    );