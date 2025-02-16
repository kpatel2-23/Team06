-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 16, 2025 at 10:58 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `login_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `content`, `created_at`, `user_id`) VALUES
(1, 3, 'liked it', '2025-02-16 15:32:46', 1),
(2, 4, 'amazing', '2025-02-16 21:15:38', 1),
(3, 4, 'perfect', '2025-02-16 21:16:16', 1),
(4, 3, 'amazing\\r\\n', '2025-02-16 21:16:54', 1),
(5, 3, 'trust', '2025-02-16 21:17:28', 1),
(6, 3, 'sasas', '2025-02-16 21:18:16', 1),
(7, 4, 'hello', '2025-02-16 21:18:31', 4);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upvotes` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `topic_id`, `title`, `content`, `created_at`, `updated_at`, `upvotes`, `user_id`) VALUES
(1, 1, 'reply to mo', 'yeah you are amazing i agree', '2025-02-16 15:02:42', '2025-02-16 15:02:42', 0, 1),
(2, 1, 'no keval you are wrong', 'he s bad', '2025-02-16 15:04:25', '2025-02-16 15:04:25', 0, 1),
(3, 2, 'dadad', 'adadad', '2025-02-16 15:05:38', '2025-02-16 21:16:26', 2, 1),
(4, 2, 'dadad', 'dadad', '2025-02-16 15:09:15', '2025-02-16 21:16:21', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `post_upvotes`
--

CREATE TABLE `post_upvotes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_upvotes`
--

INSERT INTO `post_upvotes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(1, 3, 4, '2025-02-16 15:32:41'),
(2, 4, 1, '2025-02-16 21:16:21'),
(3, 3, 1, '2025-02-16 21:16:26');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `team_leader_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `deadline` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `manager_id`, `title`, `description`, `team_leader_id`, `created_at`, `status`, `priority`, `deadline`) VALUES
(12, 4, 'NEW TEST 2 for Krish', 'NEW TEST 2 for Krish', 3, '2025-02-12 01:49:59', 'Not Started', 'Medium', '2025-02-14'),
(15, 4, 'PIE CHART', 'design bar chart', 3, '2025-02-12 19:10:37', 'Not Started', 'Medium', '2025-02-23'),
(22, 4, 'show keval', 'show keval', 3, '2025-02-13 20:15:08', 'Not Started', 'Medium', '2025-02-23'),
(26, 4, 'DENEME FOR PROJECTS', 'DENEME FOR PROJECTS', 3, '2025-02-14 23:01:59', 'Completed', 'Medium', '2025-02-15'),
(27, 4, 'EN YENI DENEMEEEE', 'EN YENI DENEMEEEE', 3, '2025-02-14 23:07:49', 'Not Started', 'Medium', '2025-02-23'),
(29, 4, 'KANBAN BOARD', 'KANBAN BOARD', 3, '2025-02-15 01:32:50', 'Completed', 'Medium', '2025-02-22'),
(32, 1, 'keval manager krish tl', 'keval manager krish tl', 3, '2025-02-15 14:40:00', 'Completed', 'Medium', '2025-02-16'),
(36, 4, 'hello task', 'hello task', 5, '2025-02-16 00:04:48', 'Not Started', 'Medium', '2025-02-23'),
(37, 4, 'edgar tl', 'edgar tl', 5, '2025-02-16 00:16:13', 'Not Started', 'Medium', '2025-02-22'),
(38, 1, 'deneme', 'deneme', 5, '2025-02-16 00:22:52', 'Not Started', 'High', '2025-02-28'),
(39, 1, 'edgar', 'edgar', 5, '2025-02-16 00:30:24', 'Completed', 'Medium', '2025-02-22'),
(40, 1, 'edgar2', 'edgar2', 5, '2025-02-16 00:30:41', 'Completed', 'Medium', '2025-02-21'),
(41, 1, 'last deneme', 'last deneme', 5, '2025-02-16 00:37:29', 'Completed', 'Medium', '2025-02-22'),
(42, 4, 'try', 'try', 10, '2025-02-16 21:22:50', 'In Progress', 'High', '2025-02-27');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `employee_id`) VALUES
(10, 12, 5),
(15, 15, 5),
(29, 22, 5),
(33, 22, 3),
(39, 26, 5),
(41, 27, 5),
(44, 29, 5),
(49, 32, 5),
(54, 37, 3),
(55, 38, 3),
(57, 39, 3),
(58, 40, 3),
(59, 41, 3),
(60, 42, 8),
(61, 42, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `deadline` date NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `status`, `deadline`, `priority`, `created_by`) VALUES
(9, 12, 'hello0', 'hello', 'Completed', '2025-02-21', 'Medium', 3),
(23, 12, 'dad', 'daadad', 'Not Started', '2025-02-16', 'High', 4),
(24, 22, 'mo taks', 'dadad', 'Not Started', '2025-02-23', 'Medium', 4),
(25, 22, 'edgar', 'dadad', 'Not Started', '2025-02-22', 'Medium', 4),
(26, 22, 'mo ;s task', 'dadad', 'Completed', '2025-02-23', 'Medium', 3),
(46, 26, 'deneme for projects', 'deneme for projects', 'Completed', '2025-02-16', 'Medium', 3),
(47, 26, 'deneme completed', 'deneme completed', 'Completed', '2025-02-16', 'Medium', 3),
(48, 27, 'JOHAN CRUYFF', 'JOHAN CRUYFF', 'Not Started', '2025-02-16', 'Medium', 4),
(49, 27, 'neymar jr', 'neymar', 'Completed', '2025-02-16', 'High', 3),
(51, 29, 'MO TASK KANBAN', 'MO TASK KANBAN', 'Completed', '2025-02-16', 'Medium', 4),
(56, 32, 'edgar', 'edgar', 'Completed', '2025-02-16', 'Medium', 1),
(57, 32, 'mo', 'mo', 'Completed', '2025-02-16', 'Medium', 1),
(62, 39, 'edgar', 'edgar', 'Completed', '2025-02-22', 'Medium', 5),
(63, 40, 'edgar2', 'edgar2', 'Completed', '2025-02-21', 'Medium', 5),
(64, 41, 'last deneme1', 'last deneme1', 'Completed', '2025-02-21', 'Medium', 5),
(65, 41, 'last deneme2', 'last deneme2', 'Completed', '2025-02-22', 'Medium', 5),
(66, 41, 'ff', 'fff', 'Completed', '2025-02-22', 'Medium', 5);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `employee_id`) VALUES
(9, 9, 3),
(23, 23, 5),
(47, 47, 5),
(54, 51, 5),
(64, 48, 5),
(66, 49, 5),
(71, 62, 3),
(72, 63, 3),
(73, 64, 3),
(74, 65, 3),
(75, 66, 3),
(77, 25, 5);

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `title`, `category`, `description`, `created_at`, `user_id`) VALUES
(1, 'test', 'Software Issues', 'tahnks to mo', '2025-02-16 14:50:57', 1),
(2, 'another topic', 'Software Development', 'dadadad', '2025-02-16 15:04:45', 1),
(3, 'sasas', 'Software Development', 'sasa', '2025-02-16 15:05:24', 4),
(4, 'gareth bale', 'Software Development', 'issues in ba', '2025-02-16 16:20:35', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('manager','employee') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Keval', 'Keval@company.com', 'b8f58c3067916bbfb50766aa8bddd42c', 'manager'),
(3, 'Krishna', 'krishna@company.com', '098f6bcd4621d373cade4e832627b4f6', 'employee'),
(4, 'Kuzey', 'kuzey@company.com', '098f6bcd4621d373cade4e832627b4f6', 'manager'),
(5, 'Edgar', 'Edgar@company.com', '098f6bcd4621d373cade4e832627b4f6', 'employee'),
(6, 'Kuzey Erturk', 'kuzey@make-it-all.co.uk', '71fea6f93dad149b7e0636ffb65c544d', 'manager'),
(7, 'krishna', 'krishna@make-it-all.co.uk', '243bd1ce0387f18005abfc43b001646a', 'manager'),
(8, 'gautam', 'gautam@company.com', '033836b6cedd9a857d82681aafadbc19', 'employee'),
(9, 'Elijah', 'elijah@company.com', '098f6bcd4621d373cade4e832627b4f6', 'employee'),
(10, 'miguel', 'miguel@company.com', '033836b6cedd9a857d82681aafadbc19', 'employee'),
(11, 'karthe', 'karthe@company.com', '098f6bcd4621d373cade4e832627b4f6', 'employee'),
(12, 'Kuzey Erturk', 'deneme@make-it-all.co.uk', '71fea6f93dad149b7e0636ffb65c544d', 'manager'),
(15, 'Jose Mourinho', 'mourinho@make-it-all.co.uk', '06704d8e398bc839b2c8a6be1373fc12', 'manager');

-- --------------------------------------------------------

--
-- Table structure for table `user_notes`
--

CREATE TABLE `user_notes` (
  `user_id` int(11) NOT NULL,
  `notes` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notes`
--

INSERT INTO `user_notes` (`user_id`, `notes`) VALUES
(4, '[]'),
(1, '[\"dadad\",\"dadadadada\",\"ALOOOOOOO\"]'),
(14, 'null'),
(15, 'null'),
(10, 'null');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_upvotes`
--
ALTER TABLE `post_upvotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_upvote` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_leader_id` (`team_leader_id`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `post_upvotes`
--
ALTER TABLE `post_upvotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_upvotes`
--
ALTER TABLE `post_upvotes`
  ADD CONSTRAINT `post_upvotes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_upvotes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`team_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
