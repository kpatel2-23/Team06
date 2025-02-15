<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "employee") {
    header("Location: index.php");
    exit();
}

$employee_id = $_SESSION["user_id"];

// Fetch Overdue Tasks
$overdue_tasks_query = $conn->query("
    SELECT COUNT(*) as count 
    FROM tasks t 
    JOIN task_assignments ta ON t.id = ta.task_id 
    WHERE ta.employee_id = $employee_id 
    AND t.deadline < NOW() 
    AND t.status != 'Completed'
");
$overdue_tasks = $overdue_tasks_query->fetch_assoc()['count'] ?? 0;

// Fetch Tasks Created by You
$tasks_created_query = $conn->query("
    SELECT COUNT(*) as count 
    FROM tasks 
    WHERE created_by = $employee_id
");
$tasks_created = $tasks_created_query->fetch_assoc()['count'] ?? 0;

// Fetch Active Projects
$active_projects_query = $conn->query("
    SELECT COUNT(DISTINCT p.id) as count 
    FROM projects p 
    LEFT JOIN project_assignments pa ON p.id = pa.project_id 
    WHERE pa.employee_id = $employee_id OR p.team_leader_id = $employee_id
");
$active_projects = $active_projects_query->fetch_assoc()['count'] ?? 0;

// Fetch statistics
$total_tasks_query = $conn->query("SELECT COUNT(*) as count FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = $employee_id");
$total_tasks = $total_tasks_query->fetch_assoc()['count'];

$completed_tasks_query = $conn->query("SELECT COUNT(*) as count FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = $employee_id AND t.status = 'completed'");
$completed_tasks = $completed_tasks_query->fetch_assoc()['count'];

$leader_projects_query = $conn->query("SELECT COUNT(*) as count FROM projects WHERE team_leader_id = $employee_id");
$leader_projects = $leader_projects_query->fetch_assoc()['count'];

$completed_projects_query = $conn->query("SELECT COUNT(*) as count FROM projects WHERE team_leader_id = $employee_id AND status = 'completed'");
$completed_projects = $completed_projects_query->fetch_assoc()['count'];

// Fetch user name
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT name FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

// Fetch only projects that still exist where the user is assigned OR is a Team Leader
$sql = "SELECT DISTINCT p.id, p.title, p.description 
        FROM projects p 
        LEFT JOIN project_assignments pa ON p.id = pa.project_id
        WHERE (pa.employee_id = ? OR p.team_leader_id = ?) 
        AND p.id IS NOT NULL";  // Ensures project still exists
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $employee_id, $employee_id);
$stmt->execute();
$projects = $stmt->get_result();

// Fetch tasks assigned to the logged-in employee
$assigned_to_me_stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.status, t.deadline, t.priority, 
           p.title AS project_name, u.name AS assigned_by
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE ta.employee_id = ? 
    AND p.id IS NOT NULL
    GROUP BY t.id
");
$assigned_to_me_stmt->bind_param("i", $employee_id);
$assigned_to_me_stmt->execute();
$tasks_assigned_to_me = $assigned_to_me_stmt->get_result();

// Fetch tasks that this employee (team leader) has assigned to others
$assigned_by_me_stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.status, t.deadline, t.priority, 
           p.title AS project_name, u.name AS assigned_to,
           creator.name AS creator_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.employee_id = u.id
    LEFT JOIN users creator ON t.created_by = creator.id
    WHERE t.created_by = ? 
    AND p.id IS NOT NULL
    GROUP BY t.id, u.name
");
$assigned_by_me_stmt->bind_param("i", $employee_id);
$assigned_by_me_stmt->execute();
$tasks_assigned_by_me = $assigned_by_me_stmt->get_result();

// Fetch only tasks that belong to existing projects
$task_stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.status, t.deadline, p.title AS project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE (ta.employee_id = ? OR t.created_by = ?) 
    AND p.id IS NOT NULL  -- Ensures project still exists
    GROUP BY t.id
");
$task_stmt->bind_param("ii", $employee_id, $employee_id);
$task_stmt->execute();
$tasks = $task_stmt->get_result();

// Check if the user is a team leader for any project
$leader_check = $conn->prepare("SELECT id, title FROM projects WHERE team_leader_id = ?");
$leader_check->bind_param("i", $employee_id);
$leader_check->execute();
$leader_result = $leader_check->get_result();
$leader_projects = [];
while ($row = $leader_result->fetch_assoc()) {
    $leader_projects[] = $row;
}
?>

<?php include("navbar.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="employee.css">
    <script src="kanban.js"></script>
    <script src="employee.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <style>
        /* Delete Task Button Styling */
        .delete-task-btn {
            background-color: #ff4d4d;
            /* Red background */
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .delete-task-btn:hover {
            background-color: #e63946;
            /* Darker red on hover */
            transform: scale(1.05);
        }

        .delete-task-btn:active {
            transform: scale(0.95);
        }

        /* Ensure consistency with other buttons */
        .task-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Confirmation Modal Styling */
        #confirmModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.6);
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #confirmModal .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            max-width: 350px;
            width: 90%;
        }

        #confirmModal p {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #333;
        }

        /* Buttons inside the confirmation modal */
        .modal-buttons {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }

        #confirmYes {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #confirmYes:hover {
            background-color: #e63946;
        }

        #confirmNo {
            background-color: #ccc;
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #confirmNo:hover {
            background-color: #bbb;
        }

        .outer-container {
            background-color: #f5f7fa;
            border-radius: 10px;
        }

        .dashboard-container {
            display: flex;
            gap: 30px;
            padding: 20px;
            border-radius: 10px;
        }

        .dashboard-left {
            flex: 1;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .welcome-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 2em;
        }

        .date {
            color: #7f8c8d;
            margin-top: 5px;
        }

        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            /* Allows wrapping on smaller screens */
            justify-content: space-between;
            /* Ensures even spacing */
            gap: 15px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            flex: 1 1 calc(33.33% - 20px);
            /* Ensures 3 cards per row on a medium screen */
            min-width: 200px;
            /* Prevents them from shrinking too much */
            max-width: 300px;
            /* Prevents them from becoming too large */
        }

        /* Hide the 5th card above 1540px */
        @media (min-width: 1540px) {
            .stat-card:nth-child(n+5) {
                display: none;
                /* Hide 5th and beyond */
            }
        }

        /* Show the 5th card above 1900px */
        @media (min-width: 1900px) {
            .stat-card:nth-child(5) {
                display: flex;
                /* Show the 5th card */
            }
        }


        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2em;
            margin-right: 15px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .total-tasks .stat-icon {
            background-color: #e3f2fd;
        }

        .completed-tasks .stat-icon {
            background-color: #e8f5e9;
        }

        .projects-leading .stat-icon {
            background-color: #fff3e0;
        }

        .completed-projects .stat-icon {
            background-color: #f3e5f5;
        }

        .stat-content h3 {
            margin: 0;
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            margin: 5px 0 0 0;
            color: #2c3e50;
        }

        .overdue-tasks .stat-icon {
            background-color: #ffebee;
            /* Light red */
        }

        .tasks-created .stat-icon {
            background-color: #e3f2fd;
            /* Light blue */
        }

        .active-projects .stat-icon {
            background-color: #f3e5f5;
            /* Light purple */
        }
    </style>
</head>

<body>
    <?php
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT name FROM users WHERE id = $user_id");
    $user = $result->fetch_assoc();
    ?>

    <div id="notificationContainer"></div>

    <div class="outer-container">
        <div class="dashboard-container">
            <div class="dashboard-left">
                <div class="welcome-section">
                    <div class="welcome-header">
                        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p class="date"><?php echo date("l, F j, Y"); ?></p>
                    </div>
                </div>

                <div class="stats-grid">
                    <!-- Overdue Tasks -->
                    <div class="stat-card overdue-tasks">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3>Overdue Tasks</h3>
                            <p class="stat-number"><?php echo $overdue_tasks; ?></p>
                        </div>
                    </div>

                    <!-- Active Projects -->
                    <div class="stat-card completed-projects">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-content">
                            <h3>Completed Projects</h3>
                            <p class="stat-number"><?php echo $completed_projects; ?></p>
                        </div>
                    </div>

                    <div class="stat-card total-tasks">
                        <div class="stat-icon">📋</div>
                        <div class="stat-content">
                            <h3>Total Tasks</h3>
                            <p class="stat-number"><?php echo $total_tasks; ?></p>
                        </div>
                    </div>

                    <div class="stat-card completed-tasks">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3>Completed Tasks</h3>
                            <p class="stat-number"><?php echo $completed_tasks; ?></p>
                        </div>
                    </div>

                    <div class="stat-card projects-leading">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-content">
                            <h3>Projects I'm Leading</h3>
                            <p class="stat-number"><?php echo count($leader_projects); ?></p>
                        </div>
                    </div>

                    <div class="stat-card active-projects">
                        <div class="stat-icon">📂</div>
                        <div class="stat-content">
                            <h3>Active Projects</h3>
                            <p class="stat-number"><?php echo $active_projects; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="projects-header">
        <h2>Projects I'm Leading</h2>
        <div class="project-filters">
            <button class="filter-btn filter-btn-projects active" data-filter="all">All</button>
            <button class="filter-btn filter-btn-projects" data-filter="in-progress">In Progress</button>
            <button class="filter-btn filter-btn-projects" data-filter="not-started">Not Started</button>
            <button class="filter-btn filter-btn-projects" data-filter="completed">Completed</button>
        </div>
    </div>

    <?php if (!empty($leader_projects)): ?>
        <div class="projects-table-container">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Project Manager</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="project-list">
                    <?php foreach ($leader_projects as $project): ?>
                        <?php
                        $proj_stmt = $conn->prepare("SELECT p.*, leader.name AS leader, manager.name AS manager, 
                                                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'In Progress') AS in_progress_tasks,
                                                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'Completed') AS completed_tasks,
                                                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS total_tasks
                                                FROM projects p 
                                                JOIN users leader ON p.team_leader_id = leader.id 
                                                JOIN users manager ON p.manager_id = manager.id 
                                                WHERE p.id = ?");
                        $proj_stmt->bind_param("i", $project["id"]);
                        $proj_stmt->execute();
                        $project_details = $proj_stmt->get_result()->fetch_assoc();

                        // Determine the project status dynamically
                        if ($project_details["total_tasks"] == 0) {
                            $project_status = "Not Started";
                        } elseif ($project_details["completed_tasks"] == $project_details["total_tasks"]) {
                            $project_status = "Completed";
                        } elseif ($project_details["in_progress_tasks"] > 0) {
                            $project_status = "In Progress";
                        } else {
                            $project_status = "Not Started";
                        }
                        ?>
                        <tr class="project-row" data-status="<?php echo strtolower(str_replace(' ', '-', $project_status)); ?>">
                            <td>
                                <div class="project-name"> <?php echo htmlspecialchars($project_details["title"]); ?> </div>
                            </td>
                            <td>
                                <div class="team-leader-info"> <span class="leader-name">
                                        <?php echo htmlspecialchars($project_details["manager"]); ?> </span> </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $project_status)); ?>">
                                    <?php echo htmlspecialchars($project_status); ?> </span>
                            </td>
                            <td>
                                <span class="priority-badge <?php echo strtolower($project_details["priority"]); ?>">
                                    <?php echo htmlspecialchars($project_details["priority"]); ?> </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn view-btn" data-project-id="<?php echo $project["id"]; ?>"> 🔍 View
                                    </button>
                                    <button class="action-btn edit-btn" data-project-id="<?php echo $project["id"]; ?>"> 🔄 Edit
                                    </button>
                                    <button class="action-btn add-task-btn" data-project-id="<?php echo $project["id"]; ?>"
                                        data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>"> ➕ Add
                                        Task </button>
                                    <button class="action-btn add-employee-btn" data-project-id="<?php echo $project["id"]; ?>"
                                        data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>"> 👥 Add
                                        Employee </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-projects-message">
            <p>You are not leading any projects currently.</p>
        </div>
    <?php endif; ?>

    <div id="no-projects-message" class="no-projects-message" style="display: none;">
        <p>No projects found in this category.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const filterButtons = document.querySelectorAll(".filter-btn-projects");
            const projectRows = document.querySelectorAll(".project-row");
            const noProjectsMessage = document.getElementById("no-projects-message");

            filterButtons.forEach(button => {
                button.addEventListener("click", function () {
                    filterButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    const filter = this.getAttribute("data-filter");
                    let hasVisibleProjects = false;

                    projectRows.forEach(row => {
                        if (filter === "all" || row.getAttribute("data-status") === filter) {
                            row.style.display = "table-row";
                            hasVisibleProjects = true;
                        } else {
                            row.style.display = "none";
                        }
                    });

                    if (!hasVisibleProjects) {
                        noProjectsMessage.style.display = "block";
                    } else {
                        noProjectsMessage.style.display = "none";
                    }
                });
            });
        });
    </script>


    <h2>My Tasks</h2>

    <div class="task-container">
        <!-- Left: Tasks Assigned to Me -->
        <div class="task-section">
            <h3>Tasks Assigned to Me</h3>
            <!-- Filters for "Tasks Assigned to Me" -->
            <div class="task-filters">
                <button class="filter-btn filter-btn-assigned-to-me active" data-filter="all">All</button>
                <button class="filter-btn filter-btn-assigned-to-me" data-filter="Not Started">Not Started</button>
                <button class="filter-btn filter-btn-assigned-to-me" data-filter="In Progress">In Progress</button>
                <button class="filter-btn filter-btn-assigned-to-me" data-filter="Completed">Completed</button>
            </div>
            <?php if ($tasks_assigned_to_me->num_rows > 0): ?>
                <ul class="tasks-list" id="tasks-assigned-to-me">
                    <?php while ($task = $tasks_assigned_to_me->fetch_assoc()): ?>
                        <li class="task-item" data-status="<?php echo htmlspecialchars($task["status"]); ?>">
                            <div class="task-info-wrapper">
                                <div class="task-main-info">
                                    <strong><?php echo htmlspecialchars($task["title"]); ?></strong>
                                    <p class="task-description"><?php echo htmlspecialchars($task["description"]); ?></p>
                                    <div class="task-details">
                                        <em>Project: <?php echo htmlspecialchars($task["project_name"]); ?></em>
                                        <span class="assigned-by">Assigned by:
                                            <?php echo htmlspecialchars($task["assigned_by"]); ?></span>
                                    </div>
                                </div>
                                <div class="task-status-wrapper">
                                    <span class="task-status">(<?php echo htmlspecialchars($task["status"]); ?>)</span>
                                    <span class="priority-badge <?php echo strtolower($task["priority"]); ?>">
                                        <?php echo htmlspecialchars($task["priority"]); ?>
                                    </span>
                                    <div class="task-actions">
                                        <?php if ($task["status"] == "Not Started"): ?>
                                            <button class="start-btn" data-task-id="<?php echo $task["id"]; ?>">Start</button>
                                        <?php elseif ($task["status"] == "In Progress"): ?>
                                            <button class="complete-btn" data-task-id="<?php echo $task["id"]; ?>">Complete</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="no-tasks-message">
                    <p>No tasks are currently assigned to you.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="confirmModal" class="modal">
            <div class="modal-content">
                <p>Are you sure you want to delete this task?</p>
                <div class="modal-buttons">
                    <button id="confirmYes">Yes</button>
                    <button id="confirmNo">Cancel</button>
                </div>
            </div>
        </div>


        <!-- Right: Tasks Assigned by Me -->
        <div class="task-section">
            <h3>Tasks I Have Assigned</h3>
            <div class="task-filters">
                <button class="filter-btn filter-btn-assigned-by-me active" data-filter="all">All</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="Not Started">Not Started</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="In Progress">In Progress</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="Completed">Completed</button>
            </div>
            <?php if ($tasks_assigned_by_me->num_rows > 0): ?>
                <ul class="tasks-list" id="tasks-assigned-by-me">
                    <?php while ($task = $tasks_assigned_by_me->fetch_assoc()): ?>
                        <li class="task-item" data-status="<?php echo htmlspecialchars($task["status"]); ?>">
                            <div class="task-info-wrapper">
                                <div class="task-main-info">
                                    <strong><?php echo htmlspecialchars($task["title"]); ?></strong>
                                    <p class="task-description"><?php echo htmlspecialchars($task["description"]); ?></p>
                                    <div class="task-details">
                                        <em>Project: <?php echo htmlspecialchars($task["project_name"]); ?></em>
                                        <span class="assigned-to">Assigned to:
                                            <?php echo htmlspecialchars($task["assigned_to"]); ?></span>
                                        <span class="deadline">Due:
                                            <?php echo date('M d, Y', strtotime($task["deadline"])); ?></span>
                                    </div>
                                </div>
                                <div class="task-status-wrapper">
                                    <span class="task-status">(<?php echo htmlspecialchars($task["status"]); ?>)</span>
                                    <span class="priority-badge <?php echo strtolower($task["priority"]); ?>">
                                        <?php echo htmlspecialchars($task["priority"]); ?>
                                    </span>
                                    <div class="task-actions">
                                        <?php if ($task["status"] == "Not Started"): ?>
                                            <button class="start-btn" data-task-id="<?php echo $task["id"]; ?>">Start</button>
                                        <?php elseif ($task["status"] == "In Progress"): ?>
                                            <button class="complete-btn" data-task-id="<?php echo $task["id"]; ?>">Complete</button>
                                        <?php endif; ?>
                                        <button class="delete-task-btn" data-task-id="<?php echo $task["id"]; ?>">🗑️</button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="no-tasks-message">
                    <p>You have not assigned any tasks to others.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let taskIdToDelete = null;

            // Open confirmation modal when clicking delete button
            document.querySelectorAll(".delete-task-btn").forEach(button => {
                button.addEventListener("click", function () {
                    taskIdToDelete = this.getAttribute("data-task-id");
                    document.getElementById("confirmModal").style.display = "flex";
                });
            });

            // Handle "Yes" button click in modal
            document.getElementById("confirmYes").addEventListener("click", function () {
                if (taskIdToDelete) {
                    fetch("delete_task.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "task_id=" + taskIdToDelete
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification("Task deleted successfully!");
                                document.querySelector(`[data-task-id='${taskIdToDelete}']`).closest("li").remove();
                            } else {
                                showNotification("Error: " + data.message);
                            }
                        })
                        .catch(error => console.error("Error:", error))
                        .finally(() => {
                            document.getElementById("confirmModal").style.display = "none";
                            taskIdToDelete = null;
                        });
                }
            });

            // Handle "No" button click
            document.getElementById("confirmNo").addEventListener("click", function () {
                document.getElementById("confirmModal").style.display = "none";
                taskIdToDelete = null;
            });

            // Close modal when clicking outside
            window.addEventListener("click", function (event) {
                if (event.target === document.getElementById("confirmModal")) {
                    document.getElementById("confirmModal").style.display = "none";
                    taskIdToDelete = null;
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            document.getElementById('taskDeadline').setAttribute('min', today); // Set the min attribute
        });
    </script>


    <!-- View Project Modal -->
    <div id="viewProjectModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <h2>Project Details: <span id="projectTitle"></span></h2>
            <p class="team-leader">Team Leader: <span id="teamLeaderName"></span></p>

            <div class="section">
                <h3>People Working</h3>
                <div id="employeeList"></div>
            </div>

            <div class="section">
                <h3>Task Distribution</h3>
                <canvas id="pieChart"></canvas>
            </div>

            <div class="section">
                <h3>Task Completion Status</h3>
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <h2>Add Task to: <span id="projectTitleForTask"></span></h2>
            <form id="addTaskForm">
                <input type="hidden" name="project_id" id="taskProjectId">

                <div class="form-group">
                    <label>Task Title</label>
                    <input type="text" name="title" placeholder="Enter task title" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Enter task description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="deadline" id="taskDeadline" required>
                </div>

                <div class="form-group">
                    <label>Priority Level</label>
                    <select name="priority" required>
                        <option value="Low">Low Priority</option>
                        <option value="Medium" selected>Medium Priority</option>
                        <option value="High">High Priority</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Assign Team Members</label>
                    <select id="taskEmployees" name="employees[]" multiple required class="select2"></select>
                </div>

                <button type="submit">Create Task</button>
            </form>
        </div>
    </div>

    <div id="addEmployeeModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <h2>Add Employees to Project: </h2>
            <span id="projectTitleForEmployees"></span>

            <div class="section">
                <h3>Recommended Employees</h3>
                <div id="recommendedEmployees" class="employee-recommendations"></div>
            </div>

            <div class="section">
                <h3>Available Employees</h3>
                <form id="assignEmployeesForm">
                    <input type="hidden" name="project_id" id="employeeProjectId">
                    <div id="availableEmployees" class="available-employees-list"></div>
                    <button type="submit" class="submit-btn">Assign Selected Employees</button>
                </form>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>

</body>

</html>