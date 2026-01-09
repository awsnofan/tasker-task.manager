CREATE TABLE users (
  userId INT AUTO_INCREMENT PRIMARY KEY,
  fullName VARCHAR(150) NOT NULL,
  username VARCHAR(60) UNIQUE NOT NULL,
  passwordHash VARCHAR(255) NOT NULL,
  email VARCHAR(120) NOT NULL,
  role ENUM('HR_ADMIN','MANAGER','EMPLOYEE') NOT NULL,
  manager_id INT NULL,
  FOREIGN KEY (manager_id) REFERENCES users(userId)
);

CREATE TABLE tasks (
  taskId INT AUTO_INCREMENT PRIMARY KEY,
  createdBy INT NOT NULL,
  assignedTo INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  priority ENUM('LOW','MEDIUM','HIGH') NOT NULL,
  status ENUM('PENDING','IN_PROGRESS','COMPLETED') NOT NULL,
  dueDate DATE NOT NULL,
  completed_at DATE NULL,
  FOREIGN KEY (createdBy) REFERENCES users(userId),
  FOREIGN KEY (assignedTo) REFERENCES users(userId)
);

CREATE TABLE task_notes (
  noteId INT AUTO_INCREMENT PRIMARY KEY,
  authorId INT NOT NULL,
  taskId INT NOT NULL,
  noteText TEXT NOT NULL,
  createdDate DATETIME NOT NULL,
  FOREIGN KEY (authorId) REFERENCES users(userId),
  FOREIGN KEY (taskId) REFERENCES tasks(taskId)
);

CREATE TABLE notifications (
  notificationId INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  taskId INT NULL,
  type ENUM('NEW_TASK','REMINDER','OVERDUE') NOT NULL,
  message VARCHAR(255) NOT NULL,
  createdDate DATETIME NOT NULL,
  isRead TINYINT(1) DEFAULT 0,
  FOREIGN KEY (userId) REFERENCES users(userId),
  FOREIGN KEY (taskId) REFERENCES tasks(taskId)
);

CREATE TABLE report_requests (
  reportrequestId INT AUTO_INCREMENT PRIMARY KEY,
  generatedByUserId INT NOT NULL,
  format ENUM('PDF','EXCEL') NOT NULL,
  filters_json TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (generatedByUserId) REFERENCES users(userId)
);
