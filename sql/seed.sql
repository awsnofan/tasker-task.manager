SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE report_requests;
TRUNCATE TABLE notifications;
TRUNCATE TABLE task_notes;
TRUNCATE TABLE tasks;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (userId, fullName, username, passwordHash, email, role, manager_id) VALUES
(1, 'Hannah Reyes', 'hradmin', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'hradmin@tasker.com', 'HR_ADMIN', NULL),
(2, 'Jordan Lee', 'manager', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'manager@tasker.com', 'MANAGER', NULL),
(3, 'Taylor Cole', 'employee', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'employee@tasker.com', 'EMPLOYEE', 2),
(4, 'Jad Dani', 'jad.dani', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'jad.dani@tasker.com', 'EMPLOYEE', 2),
(5, 'Sarah Miller', 'sarah.m', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'sarah.m@tasker.com', 'EMPLOYEE', 2),
(6, 'Alex Kim', 'alex.kim', '$2y$12$aQs/7fGaXY6RY16N87tkIeacM3BhA.efF57pDY1EhPHQ36Kh8btK.', 'alex.kim@tasker.com', 'EMPLOYEE', 2);

INSERT INTO tasks (taskId, createdBy, assignedTo, title, description, priority, status, dueDate, completed_at) VALUES
(1, 2, 3, 'Prepare quarterly sales report', 'Compile data from all departments and prepare Q3 sales report.', 'HIGH', 'IN_PROGRESS', '2024-10-12', NULL),
(2, 2, 4, 'Update onboarding documentation', 'Revise onboarding guide for new hires and update the checklist.', 'MEDIUM', 'PENDING', '2024-10-20', NULL),
(3, 2, 5, 'Client follow-up emails', 'Send follow-up emails to key clients regarding renewal.', 'LOW', 'COMPLETED', '2024-09-30', '2024-09-28'),
(4, 1, 6, 'Workplace compliance audit', 'Prepare compliance checklist and gather documentation.', 'HIGH', 'IN_PROGRESS', '2024-10-05', NULL),
(5, 1, 3, 'Employee engagement survey', 'Coordinate with HR team to launch employee survey.', 'MEDIUM', 'PENDING', '2024-10-18', NULL);

INSERT INTO task_notes (noteId, authorId, taskId, noteText, createdDate) VALUES
(1, 2, 1, 'Waiting on finance numbers for final totals.', '2024-09-21 09:15:00'),
(2, 3, 1, 'Draft report completed, pending review.', '2024-09-22 13:45:00'),
(3, 2, 2, 'Please align with branding on the updated doc.', '2024-09-23 10:05:00'),
(4, 5, 3, 'Emails sent to top 10 clients.', '2024-09-25 16:10:00');

INSERT INTO notifications (notificationId, userId, taskId, type, message, createdDate, isRead) VALUES
(1, 3, 1, 'REMINDER', 'Reminder: Quarterly sales report due in 5 days.', '2024-09-25 08:00:00', 0),
(2, 4, 2, 'NEW_TASK', 'New task assigned: Update onboarding documentation.', '2024-09-24 14:30:00', 0),
(3, 5, 3, 'OVERDUE', 'Client follow-up emails marked complete. Great work!', '2024-09-28 12:00:00', 1),
(4, 6, 4, 'REMINDER', 'Compliance audit due this week.', '2024-09-26 09:00:00', 0);
