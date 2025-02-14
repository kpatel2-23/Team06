<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "employee") {
    header("Location: index.php");
    exit();
}

$employee_id = $_SESSION["user_id"];

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
    <script src="employee.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</head>

<body>
    <?php
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT name FROM users WHERE id = $user_id");
    $user = $result->fetch_assoc();
    ?>

    <div id="notificationContainer"></div>

    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

    <div class="projects-section">
        <div class="projects-header">
            <h2>Projects I'm Leading</h2>
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
                    <tbody>
                        <?php
                        foreach ($leader_projects as $project):
                            $proj_stmt = $conn->prepare("SELECT p.*, 
                                                    leader.name AS leader, 
                                                    manager.name AS manager 
                                                FROM projects p
                                                JOIN users leader ON p.team_leader_id = leader.id
                                                JOIN users manager ON p.manager_id = manager.id
                                                WHERE p.id = ?");
                            $proj_stmt->bind_param("i", $project["id"]);
                            $proj_stmt->execute();
                            $project_details = $proj_stmt->get_result()->fetch_assoc();
                            ?>
                            <tr>
                                <td>
                                    <div class="project-name"><?php echo htmlspecialchars($project_details["title"]); ?></div>
                                </td>
                                <td>
                                    <div class="team-leader-info">
                                        <span
                                            class="leader-name"><?php echo htmlspecialchars($project_details["manager"]); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($project_details["status"]); ?>">
                                        <?php echo htmlspecialchars($project_details["status"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge <?php echo strtolower($project_details["priority"]); ?>">
                                        <?php echo htmlspecialchars($project_details["priority"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn view-btn" data-project-id="<?php echo $project["id"]; ?>">
                                            🔍 View
                                        </button>
                                        <button class="action-btn edit-btn" data-project-id="<?php echo $project["id"]; ?>">
                                            🔄 Edit
                                        </button>
                                        <button class="action-btn add-task-btn" data-project-id="<?php echo $project["id"]; ?>"
                                            data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>">
                                            ➕ Add Task
                                        </button>
                                        <button class="action-btn add-employee-btn"
                                            data-project-id="<?php echo $project["id"]; ?>"
                                            data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>">
                                            👥 Add Employee
                                        </button>
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
    </div>


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
                    <input type="date" name="deadline" required>
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