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
    SELECT t.id, t.title, t.description, t.status, t.deadline, p.title AS project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = ? 
    AND p.id IS NOT NULL
    GROUP BY t.id
");
$assigned_to_me_stmt->bind_param("i", $employee_id);
$assigned_to_me_stmt->execute();
$tasks_assigned_to_me = $assigned_to_me_stmt->get_result();

// Fetch tasks that this employee (team leader) has assigned to others
$assigned_by_me_stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.status, t.deadline, p.title AS project_name, u.name AS assigned_to
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u ON ta.employee_id = u.id
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
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

    <h2>Projects I'm Leading</h2>
    <?php if (!empty($leader_projects)): ?>
        <table>
            <tr>
                <th>Project</th>
                <th>Project Manager</th> <!-- New Column -->
                <th>Status</th>
                <th>Priority</th>
                <th>Actions</th>
            </tr>
            <?php
            foreach ($leader_projects as $project):
                // Get full project details including the project manager
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
                    <td><?php echo htmlspecialchars($project_details["title"]); ?></td>
                    <td><?php echo htmlspecialchars($project_details["manager"]); ?></td> <!-- Display Project Manager -->
                    <td><?php echo htmlspecialchars($project_details["status"]); ?></td>
                    <td><?php echo htmlspecialchars($project_details["priority"]); ?></td>
                    <td>
                        <button class="view-btn" data-project-id="<?php echo $project["id"]; ?>">🔍 View</button>
                        <button>🔄 Edit</button>
                        <button class="add-task-btn" data-project-id="<?php echo $project["id"]; ?>"
                            data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>">➕ Add Task</button>
                        <button class="add-employee-btn" data-project-id="<?php echo $project["id"]; ?>"
                            data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>">👥 Add
                            Employee</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>You are not leading any projects currently.</p>
    <?php endif; ?>


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
            <ul class="tasks-list" id="tasks-assigned-to-me">
                <?php while ($task = $tasks_assigned_to_me->fetch_assoc()): ?>
                    <li class="task-item" data-status="<?php echo htmlspecialchars($task["status"]); ?>">
                        <div class="task-info">
                            <strong><?php echo htmlspecialchars($task["title"]); ?></strong> -
                            <?php echo htmlspecialchars($task["description"]); ?>
                            <span class="task-status">(<?php echo htmlspecialchars($task["status"]); ?>)</span> -
                            <em>Project: <?php echo htmlspecialchars($task["project_name"]); ?></em>
                        </div>
                        <div class="task-actions">
                            <?php if ($task["status"] == "Not Started"): ?>
                                <button class="start-btn" data-task-id="<?php echo $task["id"]; ?>">Start</button>
                            <?php elseif ($task["status"] == "In Progress"): ?>
                                <button class="complete-btn" data-task-id="<?php echo $task["id"]; ?>">Complete</button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Right: Tasks Assigned by Me -->
        <div class="task-section">
            <h3>Tasks I Have Assigned</h3>
            <!-- Filters for "Tasks I Have Assigned" -->
            <div class="task-filters">
                <button class="filter-btn filter-btn-assigned-by-me active" data-filter="all">All</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="Not Started">Not Started</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="In Progress">In Progress</button>
                <button class="filter-btn filter-btn-assigned-by-me" data-filter="Completed">Completed</button>
            </div>
            <ul class="tasks-list" id="tasks-assigned-by-me">
                <?php while ($task = $tasks_assigned_by_me->fetch_assoc()): ?>
                    <li class="task-item" data-status="<?php echo htmlspecialchars($task["status"]); ?>">
                        <div class="task-info">
                            <strong><?php echo htmlspecialchars($task["title"]); ?></strong> -
                            <?php echo htmlspecialchars($task["description"]); ?>
                            <span class="task-status">(<?php echo htmlspecialchars($task["status"]); ?>)</span> -
                            <em>Project: <?php echo htmlspecialchars($task["project_name"]); ?></em>
                            <span class="assigned-to">Assigned to:
                                <?php echo htmlspecialchars($task["assigned_to"]); ?></span>
                        </div>
                        <div class="task-actions">
                            <?php if ($task["status"] == "Not Started"): ?>
                                <button class="start-btn" data-task-id="<?php echo $task["id"]; ?>">Start</button>
                            <?php elseif ($task["status"] == "In Progress"): ?>
                                <button class="complete-btn" data-task-id="<?php echo $task["id"]; ?>">Complete</button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>

        </div>
    </div>


    <style>
        /* Modal Styling */
        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            /* Dark transparent background */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            /* Dark transparent background */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 30px;
        }

        .employee-card {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            text-align: left;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease-in-out;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease-in-out;
            justify-content: center;
            align-items: center;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            color: red;
        }

        .close-btn:hover {
            color: darkred;
        }

        /* Fade-in Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        /* Buttons */
        button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            opacity: 0.8;
        }

        /* Task Containers */
        .task-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }

        .task-section {
            width: 48%;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h3 {
            margin-bottom: 10px;
        }

        /* Task Filters */
        .task-filters {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background-color: #000;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background-color: #f0f0f0;
        }

        .filter-btn.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        /* Task Lists */
        .tasks-list {
            list-style: none;
            padding: 0;
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
            transition: 0.3s;
        }

        .task-item:hover {
            background: #f1f1f1;
        }

        .task-info {
            flex-grow: 1;
        }

        /* Task Buttons */
        .start-btn,
        .complete-btn {
            padding: 5px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
        }

        .start-btn {
            background-color: #4CAF50;
            color: white;
        }

        .complete-btn {
            background-color: #2196F3;
            color: white;
        }

        .start-btn:hover,
        .complete-btn:hover {
            opacity: 0.8;
        }

        /* Status Text */
        .task-status {
            font-weight: bold;
            color: #666;
        }

        .assigned-to {
            font-style: italic;
            color: #888;
        }

        /* Form Styling */
        #addTaskForm {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        #addTaskForm input,
        #addTaskForm textarea,
        #addTaskForm select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #addTaskForm textarea {
            height: 100px;
            resize: vertical;
        }

        /* Select2 Dropdown */
        .select2-container {
            width: 100% !important;
        }

        /* Charts */
        #pieChart,
        #barChart {
            height: 300px !important;
        }

        .employee-recommendations {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }

        .recommended-employee-card {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #2196f3;
        }

        .available-employees-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }

        .employee-select-card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .employee-select-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>

    <script>
        async function fetchProjectDetails(projectId) {
            try {
                const response = await fetch('view_project_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `project_id=${projectId}`
                });
                return await response.json();
            } catch (error) {
                console.error('Error:', error);
                return null;
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            // View Button Functionality
            document.querySelectorAll('.view-btn').forEach(button => {
                button.addEventListener('click', async function () {
                    try {
                        const projectId = this.getAttribute('data-project-id');
                        const modal = document.getElementById('viewProjectModal');

                        const projectData = await fetchProjectDetails(projectId);
                        console.log('Project Data:', projectData);

                        if (projectData && projectData.employees) {
                            document.getElementById('projectTitle').textContent = projectData.title;
                            document.getElementById('teamLeaderName').textContent = projectData.team_leader_name;
                            displayEmployeeList(projectData.employees);
                            createPieChart(projectData.employees);
                            createBarChart(projectData.employees);
                            modal.classList.add('show');
                        } else {
                            console.error('Invalid project data:', projectData);
                            alert('Error loading project details');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading project details');
                    }
                });
            });

            // Add Task Button Functionality
            document.querySelectorAll('.add-task-btn').forEach(button => {
                button.addEventListener('click', async function () {
                    const projectId = this.getAttribute('data-project-id');
                    const projectTitle = this.getAttribute('data-project-title');
                    const modal = document.getElementById('taskModal');

                    document.getElementById('taskProjectId').value = projectId;
                    document.getElementById('projectTitleForTask').textContent = projectTitle;

                    try {
                        const response = await fetch('fetch_project_employees.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `project_id=${projectId}`
                        });
                        const employees = await response.json();

                        const employeesDropdown = document.getElementById('taskEmployees');
                        employeesDropdown.innerHTML = '';
                        employees.forEach(emp => {
                            const option = new Option(emp.name, emp.id);
                            employeesDropdown.appendChild(option);
                        });

                        // Initialize Select2
                        $(employeesDropdown).select2({
                            dropdownParent: modal
                        });

                        modal.classList.add('show');
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading employees');
                    }
                });
            });

            // Modal Close Functionality
            document.querySelectorAll('.close-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.closest('.modal').classList.remove('show');
                });
            });

            // Close on outside click
            window.onclick = function (event) {
                if (event.target.classList.contains('modal')) {
                    event.target.classList.remove('show');
                }
            };

            // Task Form Submission
            // document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
            //     e.preventDefault();
            //     try {
            //         const response = await fetch('add_task.php', {
            //             method: 'POST',
            //             body: new FormData(this)
            //         });
            //         const result = await response.text();
            //         alert(result);
            //         if (result.includes('successfully')) {
            //             location.reload();
            //         }
            //     } catch (error) {
            //         console.error('Error:', error);
            //         alert('Error creating task');
            //     }
            // });

            document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                try {
                    const response = await fetch('add_task.php', {
                        method: 'POST',
                        body: new FormData(this)
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        document.getElementById('taskModal').classList.remove('show'); // or style.display = 'none' depending on your modal implementation
                        location.reload();
                    } else {
                        alert(result.error || 'Error creating task');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error creating task');
                }
            });

            function createPieChart(employees) {
                const ctx = document.getElementById('pieChart').getContext('2d');

                if (window.taskDistributionChart) {
                    window.taskDistributionChart.destroy();
                }

                const data = {
                    labels: employees.map(emp => emp.name),
                    datasets: [{
                        data: employees.map(emp => emp.total_tasks),
                        backgroundColor: employees.map((_, idx) =>
                            `hsl(${(idx * 360) / employees.length}, 70%, 60%)`
                        )
                    }]
                };

                window.taskDistributionChart = new Chart(ctx, {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }

            function createBarChart(employees) {
                const ctx = document.getElementById('barChart').getContext('2d');

                if (window.taskCompletionChart) {
                    window.taskCompletionChart.destroy();
                }

                const data = {
                    labels: employees.map(emp => emp.name),
                    datasets: [
                        {
                            label: 'Completed Tasks',
                            data: employees.map(emp => emp.completed_tasks),
                            backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        },
                        {
                            label: 'Pending Tasks',
                            data: employees.map(emp => emp.pending_tasks),
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        }
                    ]
                };

                window.taskCompletionChart = new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            function displayEmployeeList(employees) {
                if (!Array.isArray(employees)) {
                    console.error('Invalid employees data:', employees);
                    return;
                }

                const employeeList = document.getElementById('employeeList');
                employeeList.innerHTML = '';

                employees.forEach(emp => {
                    const card = document.createElement('div');
                    card.className = 'employee-card';
                    card.innerHTML = `
            <strong>${emp.name}</strong><br>
            Total Tasks: ${emp.total_tasks}<br>
            Completed: ${emp.completed_tasks}<br>
            Pending: ${emp.pending_tasks}
        `;
                    employeeList.appendChild(card);
                });
            }
        });

        // Task status update functionality
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('start-btn') || e.target.classList.contains('complete-btn')) {
                const taskId = e.target.getAttribute('data-task-id');
                const newStatus = e.target.classList.contains('start-btn') ? 'In Progress' : 'Completed';

                updateTaskStatus(taskId, newStatus, e.target);
            }
        });

        // Ensure updates also apply to the "Tasks I Have Assigned" section
        async function updateTaskStatus(taskId, newStatus, button) {
            try {
                const response = await fetch('update_task_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&status=${encodeURIComponent(newStatus)}`
                });

                const result = await response.json();

                if (result.success) {
                    const taskItem = button.closest('.task-item');
                    const statusSpan = taskItem.querySelector('.task-status');
                    statusSpan.textContent = `(${newStatus})`;

                    // Update data-status attribute
                    taskItem.setAttribute('data-status', newStatus);

                    // Update button
                    if (newStatus === 'In Progress') {
                        button.textContent = 'Complete';
                        button.classList.remove('start-btn');
                        button.classList.add('complete-btn');
                    } else if (newStatus === 'Completed') {
                        button.remove();
                    }

                    // Handle visibility based on current filter
                    const activeFilter = document.querySelector('.filter-btn.active');
                    const currentFilter = activeFilter.getAttribute('data-filter');

                    if (currentFilter !== 'all' && currentFilter !== newStatus) {
                        taskItem.style.display = 'none';
                    } else {
                        taskItem.style.display = '';
                    }

                    alert(`Task status updated to ${newStatus}`);
                } else {
                    alert(result.error || 'Failed to update task status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating task status');
            }
        }



        document.addEventListener('DOMContentLoaded', function () {
            function setupFilters(filterButtonsSelector, taskListSelector) {
                const filterButtons = document.querySelectorAll(filterButtonsSelector);
                const taskList = document.querySelector(taskListSelector);

                filterButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        const filter = button.getAttribute('data-filter');

                        // Remove "active" class only from buttons in the same section
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');

                        // Apply filter only to tasks in the related section
                        taskList.querySelectorAll('.task-item').forEach(task => {
                            const taskStatus = task.getAttribute('data-status');
                            if (filter === 'all' || filter === taskStatus) {
                                task.style.display = 'flex';
                            } else {
                                task.style.display = 'none';
                            }
                        });
                    });
                });
            }

            // Setup independent filters for both task sections
            setupFilters('.filter-btn-assigned-to-me', '#tasks-assigned-to-me');
            setupFilters('.filter-btn-assigned-by-me', '#tasks-assigned-by-me');
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.add-employee-btn').forEach(button => {
                button.addEventListener('click', async function () {
                    const projectId = this.getAttribute('data-project-id');
                    const projectTitle = this.getAttribute('data-project-title');
                    const modal = document.getElementById('addEmployeeModal');

                    document.getElementById('employeeProjectId').value = projectId;
                    document.getElementById('projectTitleForEmployees').textContent = projectTitle;

                    try {
                        const response = await fetch('fetch_available_employees.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `project_id=${projectId}`
                        });
                        const data = await response.json();

                        // Display recommended employees
                        const recommendedContainer = document.getElementById('recommendedEmployees');
                        recommendedContainer.innerHTML = data.recommended.map(emp => `
                    <div class="recommended-employee-card">
                        <strong>${emp.name}</strong>
                        <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                        <div>⭐ Recommended due to low workload</div>
                    </div>
                `).join('');

                        // Display all available employees
                        const availableContainer = document.getElementById('availableEmployees');
                        availableContainer.innerHTML = data.all_employees.map(emp => `
                    <div class="employee-select-card">
                        <input type="checkbox" name="employee_ids[]" value="${emp.id}">
                        <div>
                            <strong>${emp.name}</strong>
                            <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                        </div>
                    </div>
                `).join('');

                        modal.classList.add('show');
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading employees');
                    }
                });
            });

            // Handle employee assignment form submission
            document.getElementById('assignEmployeesForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                try {
                    const response = await fetch('assign_employees.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert('Employees assigned successfully!');
                        location.reload();
                    } else {
                        alert(result.error || 'Failed to assign employees');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error assigning employees');
                }
            });
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
            <h2>Create Task for: <span id="projectTitleForTask"></span></h2>
            <form id="addTaskForm">
                <input type="hidden" name="project_id" id="taskProjectId">

                <label>Task Title:</label>
                <input type="text" name="title" required>

                <label>Description:</label>
                <textarea name="description" required></textarea>

                <label>Deadline:</label>
                <input type="date" name="deadline" required>

                <label>Priority:</label>
                <select name="priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>

                <label>Assign Employees:</label>
                <select id="taskEmployees" name="employees[]" multiple required></select>

                <button type="submit">Create Task</button>
            </form>
        </div>
    </div>
    <div id="addEmployeeModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <h2>Add Employees to Project: <span id="projectTitleForEmployees"></span></h2>

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