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
    <a href="logout.php">Logout</a>

    <h2>Projects I'm Leading</h2>
    <?php if (!empty($leader_projects)): ?>
        <table>
            <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Actions</th>
            </tr>
            <?php
            foreach ($leader_projects as $project):
                // Get full project details
                $proj_stmt = $conn->prepare("SELECT p.*, u.name as leader 
                                       FROM projects p 
                                       JOIN users u ON p.team_leader_id = u.id 
                                       WHERE p.id = ?");
                $proj_stmt->bind_param("i", $project["id"]);
                $proj_stmt->execute();
                $project_details = $proj_stmt->get_result()->fetch_assoc();
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($project_details["title"]); ?></td>
                    <td><?php echo htmlspecialchars($project_details["status"]); ?></td>
                    <td><?php echo htmlspecialchars($project_details["priority"]); ?></td>
                    <td>
                        <button class="view-btn" data-project-id="<?php echo $project["id"]; ?>">🔍 View</button>
                        <button>🔄 Edit</button>
                        <button class="add-task-btn" data-project-id="<?php echo $project["id"]; ?>"
                            data-project-title="<?php echo htmlspecialchars($project_details["title"]); ?>">➕ Add Task</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>You are not leading any projects currently.</p>
    <?php endif; ?>

    <h2>My Tasks</h2>
    <ul>
        <?php while ($task = $tasks->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($task["title"]); ?></strong> -
                <?php echo htmlspecialchars($task["description"]); ?>
                (<?php echo htmlspecialchars($task["status"]); ?>) -
                <em>Project: <?php echo htmlspecialchars($task["project_name"]); ?></em>
            </li>
        <?php endwhile; ?>
    </ul>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            position: relative;
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 10px;
        }

        .close-btn:hover {
            color: red;
        }

        .section {
            margin: 20px 0;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .employee-card {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }

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

        button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        #pieChart,
        #barChart {
            height: 300px !important;
        }

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
        }

        #addTaskForm textarea {
            height: 100px;
            resize: vertical;
        }

        .select2-container {
            width: 100% !important;
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
                            modal.style.display = 'block';
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

                        modal.style.display = 'block';
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading employees');
                    }
                });
            });

            // Modal Close Functionality
            document.querySelectorAll('.close-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.closest('.modal').style.display = 'none';
                });
            });

            // Close on outside click
            window.onclick = function (event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };

            // Task Form Submission
            document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                try {
                    const response = await fetch('add_task.php', {
                        method: 'POST',
                        body: new FormData(this)
                    });
                    const result = await response.text();
                    alert(result);
                    if (result.includes('successfully')) {
                        location.reload();
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

</body>

</html>