<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    header("Location: index.php");
    exit();
}

$manager_id = $_SESSION['user_id'];

// Get statistics only for this manager's projects
$project_count = $conn->query("SELECT COUNT(*) as count FROM projects WHERE manager_id = $manager_id")->fetch_assoc()['count'];
$employee_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='employee'")->fetch_assoc()['count'];
$completed_projects = $conn->query("SELECT COUNT(*) as count FROM projects WHERE manager_id = $manager_id AND status='Completed'")->fetch_assoc()['count'];
$ongoing_projects = $conn->query("SELECT COUNT(*) as count FROM projects WHERE manager_id = $manager_id AND status='In Progress'")->fetch_assoc()['count'];

// Get employees and their workload
$workload_result = $conn->query("
    SELECT u.id, u.name, 
    COUNT(pa.project_id) as assigned_tasks, 
    SUM(CASE WHEN p.status='Completed' THEN 1 ELSE 0 END) as completed_tasks 
    FROM users u 
    LEFT JOIN project_assignments pa ON u.id = pa.employee_id 
    LEFT JOIN projects p ON pa.project_id = p.id 
    WHERE u.role='employee'
    GROUP BY u.id, u.name
");

// Fetch only projects created by the logged-in manager
$query = "SELECT p.*, u.name as leader 
          FROM projects p 
          JOIN users u ON p.team_leader_id = u.id 
          WHERE p.manager_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$projects = $stmt->get_result();
?>



<?php include("navbar.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
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
            <!-- Left Side -->
            <div class="dashboard-left">
                <div class="welcome-section">
                    <div class="welcome-header">
                        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p class="date"><?php echo date("l, F j, Y"); ?></p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card total-projects">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-content">
                            <h3>Total Projects</h3>
                            <p class="stat-number"><?php echo $project_count; ?></p>
                        </div>
                    </div>

                    <div class="stat-card total-employees">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3>Total Employees</h3>
                            <p class="stat-number"><?php echo $employee_count; ?></p>
                        </div>
                    </div>

                    <div class="stat-card completed-projects">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3>Completed Projects</h3>
                            <p class="stat-number"><?php echo $completed_projects; ?></p>
                        </div>
                    </div>

                    <div class="stat-card ongoing-projects">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3>Ongoing Projects</h3>
                            <p class="stat-number"><?php echo $ongoing_projects; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div class="dashboard-right">
                <div class="workload-section">
                    <h2>Employee Workload Overview</h2>
                    <div class="workload-table-container">
                        <table class="workload-table">
                            <tr>
                                <th>Employee</th>
                                <th>Assigned Tasks</th>
                                <th>Completed</th>
                                <th>Remaining</th>
                            </tr>
                            <?php while ($row = $workload_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($row["assigned_tasks"]); ?></span>
                                    </td>
                                    <td><span
                                            class="badge success"><?php echo htmlspecialchars($row["completed_tasks"]); ?></span>
                                    </td>
                                    <td><span
                                            class="badge warning"><?php echo htmlspecialchars($row["assigned_tasks"] - $row["completed_tasks"]); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-bottom">
            <div class="assigned-tasks-section">
                <div class="section-header">
                    <h2>Tasks I Have Assigned</h2>
                </div>
                <div class="tasks-container">
                    <?php
                    // Fetch tasks assigned by this manager
                    $assigned_tasks_query = "
                    SELECT t.*, p.title as project_name, u.name as employee_name, p.priority as project_priority
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    JOIN task_assignments ta ON t.id = ta.task_id
                    JOIN users u ON ta.employee_id = u.id
                    WHERE t.created_by = ?
                    ORDER BY t.deadline ASC";

                    $task_stmt = $conn->prepare($assigned_tasks_query);
                    $task_stmt->bind_param("i", $manager_id);
                    $task_stmt->execute();
                    $assigned_tasks = $task_stmt->get_result();
                    ?>

                    <div class="tasks-grid">
                        <?php while ($task = $assigned_tasks->fetch_assoc()): ?>
                            <div class="task-card">
                                <div class="task-header">
                                    <div class="task-project">
                                        <span class="label">Project:</span>
                                        <span
                                            class="project-name"><?php echo htmlspecialchars($task["project_name"]); ?></span>
                                    </div>
                                    <span class="task-status <?php echo strtolower($task["status"]); ?>">
                                        <?php echo htmlspecialchars($task["status"]); ?>
                                    </span>

                                </div>
                                <div class="task-title">
                                    <?php echo htmlspecialchars($task["title"]); ?>
                                </div>
                                <div class="task-details">
                                    <div class="task-assignee">
                                        <span class="label">Assigned to:</span>
                                        <?php echo htmlspecialchars($task["employee_name"]); ?>
                                    </div>
                                    <div class="task-deadline">
                                        <span class="label">Deadline:</span>
                                        <?php echo date('M d, Y', strtotime($task["deadline"])); ?>
                                    </div>
                                </div>
                                <div class="task-description">
                                    <?php echo htmlspecialchars($task["description"]); ?>
                                </div>
                                <div class="task-priority">
                                    <span class="label">Priority:</span>
                                    <span class="priority-badge <?php echo strtolower($task["priority"]); ?>">
                                        <?php echo htmlspecialchars($task["priority"]); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="projects-section">
        <div class="projects-header">
            <h2>Active Projects</h2>
            <button id="openModal" class="add-project-btn">➕ Add Project</button>
        </div>

        <div class="projects-table-container">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Team Leader</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $projects->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="project-name"><?php echo htmlspecialchars($row["title"]); ?></div>
                            </td>
                            <td>
                                <div class="team-leader-info">
                                    <span class="leader-name"><?php echo htmlspecialchars($row["leader"]); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($row["status"]); ?>">
                                    <?php echo htmlspecialchars($row["status"]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="priority-badge <?php echo strtolower($row["priority"]); ?>">
                                    <?php echo htmlspecialchars($row["priority"]); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn view-btn" data-project-id="<?php echo $row['id']; ?>">
                                        🔍 View
                                    </button>
                                    <button class="action-btn edit-btn" data-project-id="<?php echo $row['id']; ?>">
                                        🔄 Edit
                                    </button>
                                    <button class="action-btn add-task-btn" data-project-id="<?php echo $row['id']; ?>"
                                        data-project-title="<?php echo htmlspecialchars($row['title']); ?>">
                                        ➕ Add Task
                                    </button>
                                    <button class="action-btn add-employee-btn" data-project-id="<?php echo $row['id']; ?>"
                                        data-project-title="<?php echo htmlspecialchars($row['title']); ?>">
                                        👥 Add Employee
                                    </button>
                                    <button class="action-btn delete-btn" data-project-id="<?php echo $row['id']; ?>">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Adding Project -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <h2>Create New Project</h2>
            <form id="addProjectForm">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" placeholder="Enter project title" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Enter project description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Deadline</label>
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
                    <label>Team Leader</label>
                    <select id="team_leader" name="team_leader" class="select2" required></select>
                </div>

                <div class="form-group">
                    <label>Team Members</label>
                    <select id="employees" name="employees[]" class="select2" multiple required></select>
                </div>

                <button type="submit">Create Project</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let projectIdToDelete = null;
            let deleteButton = null;

            // Open confirmation modal when clicking delete button
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function () {
                    projectIdToDelete = this.getAttribute("data-project-id");
                    deleteButton = this;
                    document.getElementById("confirmModal").style.display = "flex";
                });
            });

            // Handle "Yes" button click
            document.getElementById("confirmYes").addEventListener("click", function () {
                if (projectIdToDelete) {
                    fetch("delete_project.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "project_id=" + projectIdToDelete
                    })
                        .then(response => response.text())
                        .then(result => {
                            if (result === "success") {
                                showNotification("Project deleted successfully!", "success");

                                // Remove the project row from the UI
                                if (deleteButton) {
                                    const projectRow = deleteButton.closest("tr");
                                    if (projectRow) {
                                        projectRow.remove();
                                    }
                                }
                            } else {
                                showNotification("Error deleting project: " + result, "error");
                            }
                        })
                        .catch(error => {
                            showNotification("Error deleting project: " + error, "error");
                        })
                        .finally(() => {
                            document.getElementById("confirmModal").style.display = "none";
                            projectIdToDelete = null;
                            deleteButton = null;
                        });
                }
            });

            // Handle "No" button click
            document.getElementById("confirmNo").addEventListener("click", function () {
                document.getElementById("confirmModal").style.display = "none";
                projectIdToDelete = null;
                deleteButton = null;
            });

            // Close modal when clicking outside
            window.addEventListener("click", function (event) {
                if (event.target === document.getElementById("confirmModal")) {
                    document.getElementById("confirmModal").style.display = "none";
                    projectIdToDelete = null;
                    deleteButton = null;
                }
            });
        });

        // Notification system
        function showNotification(message, type) {
            const notificationContainer = document.getElementById("notificationContainer");

            const notification = document.createElement("div");
            notification.classList.add("notification", type);
            notification.innerText = message;

            notificationContainer.appendChild(notification);

            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = "0";
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }



        document.addEventListener("DOMContentLoaded", function () {
            document.body.addEventListener("click", async function (event) {
                if (event.target.classList.contains("add-task-btn")) {
                    const projectId = event.target.getAttribute("data-project-id");

                    // 🛠 **Force employee refresh when opening modal**
                    await loadProjectEmployees(projectId);
                }
            });
        });


        // Task Modal Functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Add Task button click handler
            document.querySelectorAll('.add-task-btn').forEach(button => {
                button.addEventListener('click', async function () {
                    const projectId = this.getAttribute('data-project-id');
                    const projectTitle = this.getAttribute('data-project-title');
                    const taskModal = document.getElementById('taskModal');

                    // Set project information
                    document.getElementById('taskProjectId').value = projectId;
                    document.getElementById('projectTitleForTask').textContent = projectTitle;

                    // Fetch employees assigned to this project
                    try {
                        const response = await fetch('fetch_project_employees.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `project_id=${projectId}`
                        });
                        const employees = await response.json();

                        // Populate employee dropdown
                        const employeesDropdown = document.getElementById('taskEmployees');
                        employeesDropdown.innerHTML = '';
                        employees.forEach(emp => {
                            const option = new Option(emp.name, emp.id);
                            employeesDropdown.appendChild(option);
                        });

                        // Initialize Select2 for the employees dropdown
                        $(employeesDropdown).select2({
                            dropdownParent: taskModal
                        });

                        // Show the modal
                        taskModal.style.display = 'block';
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading employees');
                    }
                });
            });

            // Task form submission
            // document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
            //     e.preventDefault();

            //     try {
            //         const response = await fetch('add_task.php', {
            //             method: 'POST',
            //             body: new FormData(this)
            //         });
            //         const result = await response.text();
            //         alert(result);
            //         if (result.includes('success')) {
            //             document.getElementById('taskModal').style.display = 'none';
            //             location.reload();
            //         }
            //     } catch (error) {
            //         console.error('Error:', error);
            //         alert('Error creating task');
            //     }
            // });

            document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                try {
                    const response = await fetch('add_task.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json(); // Ensure your PHP returns JSON

                    // Close the modal immediately
                    document.getElementById('taskModal').classList.remove('show'); // or style.display = 'none';

                    if (result.success) {
                        showNotification(result.message || 'Task added successfully!', 'success');

                        // Clear the form
                        this.reset();

                        // Reload after 2 seconds to show new task
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotification(result.error || 'Error creating task!', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error creating task!', 'error');
                }
            });

            // External Notification System Function
            function showNotification(message, type) {
                const notificationContainer = document.getElementById('notificationContainer');

                const notification = document.createElement('div');
                notification.classList.add('notification', type);
                notification.innerText = message;

                notificationContainer.appendChild(notification);

                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 500); // Wait for fade-out before removing
                }, 3000);
            }




            // Close task modal
            const taskModal = document.getElementById('taskModal');
            const taskCloseBtn = taskModal.querySelector('.close-btn');

            taskCloseBtn.addEventListener('click', function () {
                taskModal.style.display = 'none';
            });

            // Close on outside click
            window.addEventListener('click', function (event) {
                if (event.target === taskModal) {
                    taskModal.style.display = 'none';
                }
            });
        });
    </script>

    <script>
        function closeModal() {
            document.getElementById("projectModal").style.display = "none";
        }

        document.getElementById("openModal").onclick = function () {
            document.getElementById("projectModal").style.display = "block";
        };

        window.onclick = function (event) {
            let modal = document.getElementById("projectModal");
            if (event.target === modal) {
                closeModal();
            }
        };

        // Initialize Select2
        $(document).ready(function () {
            $('.select2').select2();
        });

        // Fetch employees dynamically
        fetch("fetch_employees.php")
            .then(response => response.json())
            .then(data => {
                let leaderDropdown = document.getElementById("team_leader");
                let employeesDropdown = document.getElementById("employees");

                data.forEach(emp => {
                    let option = `<option value="${emp.id}">${emp.name}</option>`;
                    leaderDropdown.innerHTML += option;
                    employeesDropdown.innerHTML += option;
                });
            });

        // Handle form submission (AJAX)
        document.getElementById("addProjectForm").onsubmit = function (e) {
            e.preventDefault();
            let formData = new FormData(this);

            fetch("add_project.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification("Project added successfully!", "success");
                        // Optionally, you can reload the page or update the UI dynamically
                        setTimeout(() => location.reload(), 2000); // Reload after 2 seconds
                    } else {
                        showNotification("Error adding project: " + result.error, "error");
                    }
                })
                .catch(error => {
                    showNotification("Error adding project: " + error, "error");
                });
        };
    </script>

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

        function displayEmployeeList(employees) {
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
                        x: {
                            stacked: false
                        },
                        y: {
                            beginAtZero: true,
                            stacked: false,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Update the view button click handler in your existing script
        document.addEventListener('DOMContentLoaded', function () {
            // Find all buttons and filter for the ones with View text
            document.querySelectorAll('button').forEach(button => {
                if (button.textContent.includes('🔍 View')) {
                    button.onclick = async function () {
                        const projectId = this.closest('tr').querySelector('.delete-btn').getAttribute('data-project-id');
                        const modal = document.getElementById('viewProjectModal');

                        if (!modal) {
                            console.error('Modal not found!');
                            return;
                        }

                        const projectData = await fetchProjectDetails(projectId);
                        console.log('Project Data:', projectData); // For debugging

                        if (projectData) {
                            document.getElementById('projectTitle').textContent = projectData.title;
                            document.getElementById('teamLeaderName').textContent = projectData.team_leader_name;
                            displayEmployeeList(projectData.employees);
                            createPieChart(projectData.employees);
                            createBarChart(projectData.employees);
                            modal.style.display = 'block';
                        }
                    };
                }
            });
        });

        // Close modal when clicking (x) or outside
        document.addEventListener('DOMContentLoaded', function () {
            // View button functionality (keep your existing view button code)

            // Add this for modal close functionality
            const viewModal = document.getElementById('viewProjectModal');
            const closeBtn = viewModal.querySelector('.close-btn');

            // Close on X button click
            closeBtn.addEventListener('click', function () {
                viewModal.style.display = 'none';
            });

            // Close on outside click
            window.addEventListener('click', function (event) {
                if (event.target == viewModal) {
                    viewModal.style.display = 'none';
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".add-employee-btn").forEach(button => {
                button.addEventListener("click", async function () {
                    const projectId = this.getAttribute("data-project-id");
                    const projectTitle = this.getAttribute("data-project-title");
                    const modal = document.getElementById("addEmployeeModal");

                    document.getElementById("employeeProjectId").value = projectId;
                    document.getElementById("projectTitleForEmployees").textContent = projectTitle;

                    try {
                        const response = await fetch('fetch_available_employees.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `project_id=${projectId}`
                        });
                        const data = await response.json();

                        // Display recommended employees
                        const recommendedContainer = document.getElementById("recommendedEmployees");
                        recommendedContainer.innerHTML = data.recommended.map(emp => `
                    <div class="recommended-employee-card">
                        <strong>${emp.name}</strong>
                        <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                        <div>⭐ Recommended due to low workload</div>
                    </div>
                `).join("");

                        // Display all available employees
                        const availableContainer = document.getElementById("availableEmployees");
                        availableContainer.innerHTML = data.all_employees.map(emp => `
                    <div class="employee-select-card">
                        <input type="checkbox" name="employee_ids[]" value="${emp.id}">
                        <div>
                            <strong>${emp.name}</strong>
                            <div class="task-count">Total Tasks: ${emp.total_tasks}</div>
                        </div>
                    </div>
                `).join("");

                        modal.style.display = "block";
                    } catch (error) {
                        console.error("Error:", error);
                        alert("Error loading employees");
                    }
                });
            });

            // Handle employee assignment form submission dynamically
            document.getElementById("assignEmployeesForm").addEventListener("submit", async function (e) {
                e.preventDefault();

                let formData = new FormData(this);
                const projectId = document.getElementById("employeeProjectId").value;

                try {
                    const response = await fetch("assign_employees.php", {
                        method: "POST",
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        showNotification("Employees assigned successfully!", "success");

                        // Hide the modal after assignment
                        document.getElementById("addEmployeeModal").style.display = "none";

                        // Update employee list dynamically without reloading
                        updateEmployeeList(projectId);
                    } else {
                        showNotification("Error assigning employees: " + result.error, "error");
                    }
                } catch (error) {
                    console.error("Error:", error);
                    showNotification("Error assigning employees!", "error");
                }
            });

            // Close the modal when clicking (X) or outside
            const addEmployeeModal = document.getElementById("addEmployeeModal");
            const addEmployeeCloseBtn = addEmployeeModal.querySelector(".close-btn");

            addEmployeeCloseBtn.addEventListener("click", function () {
                addEmployeeModal.style.display = "none";
            });

            window.addEventListener("click", function (event) {
                if (event.target === addEmployeeModal) {
                    addEmployeeModal.style.display = "none";
                }
            });

            // Function to update employee list dynamically
            async function updateEmployeeList(projectId) {
                try {
                    const response = await fetch("fetch_project_employees.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `project_id=${projectId}`
                    });
                    const employees = await response.json();

                    // Update the employee list in the "View Project" modal
                    const employeeList = document.getElementById("employeeList");
                    employeeList.innerHTML = employees.map(emp => `
                <div class="employee-card">
                    <strong>${emp.name}</strong><br>
                    Total Tasks: ${emp.total_tasks}<br>
                    Completed: ${emp.completed_tasks}<br>
                    Pending: ${emp.pending_tasks}
                </div>
            `).join("");

                    showNotification("Employee list updated!", "success");
                } catch (error) {
                    console.error("Error fetching updated employees:", error);
                }
            }
        });



        document.addEventListener('DOMContentLoaded', function () {
            // Add Employee Modal close functionality
            const addEmployeeModal = document.getElementById('addEmployeeModal');
            const addEmployeeCloseBtn = addEmployeeModal.querySelector('.close-btn');

            // Close on X button click
            addEmployeeCloseBtn.addEventListener('click', function () {
                addEmployeeModal.style.display = 'none';
            });

            // Close on outside click
            window.addEventListener('click', function (event) {
                if (event.target === addEmployeeModal) {
                    addEmployeeModal.style.display = 'none';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const taskCards = document.querySelectorAll('.task-card');
            taskCards.forEach(card => {
                card.addEventListener('click', function () {
                    showTaskModal(this);
                });
            });
        });

        function showTaskModal(taskCard) {
            const modal = document.getElementById('taskDetailModal');

            // Get all the necessary elements from the task card
            const projectName = taskCard.querySelector('.project-name').textContent;
            const taskTitle = taskCard.querySelector('.task-title').textContent;
            const status = taskCard.querySelector('.task-status').textContent.trim();
            const assignee = taskCard.querySelector('.task-assignee').textContent.replace('Assigned to:', '').trim();
            const deadline = taskCard.querySelector('.task-deadline').textContent.replace('Deadline:', '').trim();
            const description = taskCard.querySelector('.task-description').textContent;
            const priority = taskCard.querySelector('.priority-badge').textContent.trim();

            // Populate the modal with the task data
            document.getElementById('modalTaskTitle').textContent = taskTitle;
            document.getElementById('modalProjectName').textContent = projectName;
            document.getElementById('modalAssignee').textContent = assignee;
            document.getElementById('modalDeadline').textContent = deadline;
            document.getElementById('modalDescription').textContent = description;
            document.getElementById('modalPriority').textContent = priority;

            // Set status badge
            const statusBadge = document.getElementById('modalTaskStatus');
            statusBadge.textContent = status;
            statusBadge.className = 'status-badge ' + status.toLowerCase();

            // Set progress indicator based on status
            const progressIndicator = document.getElementById('progressIndicator');
            // Normalize the status by removing spaces and converting to lowercase
            const normalizedStatus = status.toLowerCase().replace(/\s+/g, '');
            switch (normalizedStatus) {
                case 'pending':
                    progressIndicator.style.width = '0%';
                    break;
                case 'inprogress':
                    progressIndicator.style.width = '50%';
                    break;
                case 'completed':
                    progressIndicator.style.width = '100%';
                    break;
                default:
                    console.log('Unknown status:', status);
                    progressIndicator.style.width = '0%';
            }

            // Show the modal
            modal.style.display = 'flex';

            // Close modal when clicking outside
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeTaskModal();
                }
            });
        }

        function closeTaskModal() {
            const modal = document.getElementById('taskDetailModal');
            modal.style.display = 'none';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeTaskModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeTaskModal();
            }
        });


    </script>

    <style>
        /* Confirmation Modal Styles */
        #confirmModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #confirmModal .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            height: 150px;
        }

        #confirmModal p {
            margin-bottom: 20px;
            font-size: 1.1em;
            color: #333;
        }

        #confirmModal .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        #confirmModal button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        #confirmYes {
            background-color: #F8CE08;
            color: white;
        }

        #confirmYes:hover {
            background-color: #e6b800;
        }

        #confirmNo {
            background-color: #f44336;
            color: white;
        }

        #confirmNo:hover {
            background-color: #d32f2f;
        }

        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            margin-right: 30px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            padding: 12px 20px;
            border-radius: 5px;
            margin-right: 0px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            width: 100%;
            height: 100%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            height: 600px;
            max-width: 600px;
            margin: 50 auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
            overflow-y: auto;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Close Button */
        .close-btn,
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #666;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover,
        .close:hover {
            background: #f0f0f0;
            color: #333;
        }

        /* Form Styles */
        .modal h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #F8CE08;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        /* Select2 Customization */
        .select2-container {
            margin-bottom: 15px;
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            min-height: 45px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #F8CE08 !important;
            border: none !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 5px 10px !important;
        }

        /* Submit Button */
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: #F8CE08;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.2);
        }

        /* Priority Select Styling */
        select[name="priority"] option[value="High"] {
            color: #e53e3e;
        }

        select[name="priority"] option[value="Medium"] {
            color: #d69e2e;
        }

        select[name="priority"] option[value="Low"] {
            color: #38a169;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 20px auto;
                padding: 20px;
            }
        }

        .section {
            margin: 20px 0;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        #employeeList {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }

        #pieChart {
            height: 200px !important;
            /* Smaller height for pie chart */
            width: 100% !important;
            max-width: 400px !important;
            /* Limit maximum width */
            margin: 0 auto !important;
            /* Center the chart */
        }

        .employee-card {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            text-align: left;
        }

        #barChart {
            height: 250px !important;
            /* Height for bar chart */
            width: 100% !important;
        }

        .chart-container {
            height: 300px;
            margin: 20px 0;
        }

        .section:has(canvas) {
            padding: 10px;
            margin: 10px 0;
        }

        .team-leader {
            margin: 10px 0 20px 0;
            font-size: 16px;
            color: #666;
        }

        .team-leader span {
            font-weight: bold;
            color: #333;
        }

        .select2-container {
            width: 100% !important;
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

        #addTaskForm button[type="submit"] {
            background-color: #F8CE08;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #addTaskForm button[type="submit"]:hover {
            background-color: #45a049;
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

        .task-count {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .submit-btn {
            background-color: #F8CE08;
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

        .kanban-modal-content {
            width: 95% !important;
            max-width: 1400px !important;
            height: 90vh;
            padding: 20px;
        }

        .kanban-row-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding: 10px;
        }

        .kanban-column {
            min-width: 300px;
            background: #f5f5f5;
            border-radius: 8px;
            padding: 10px;
        }

        .kanban-column h3 {
            margin: 0 0 10px 0;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
        }

        .kanban-task-list {
            min-height: 100px;
            padding: 10px;
        }

        .task-card {
            background: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: move;
        }

        .task-card.overdue {
            border-left: 4px solid red;
        }

        .task-section {
            margin: 5px 0;
        }

        .filter-section {
            margin-bottom: 20px;
        }

        #attributeFilterKanban {
            width: 300px;
            padding: 8px;
            margin: 10px 0;
        }

        #rearrangeBtn {
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #F8CE08;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #rearrangeBtn:hover {
            background: #45a049;
        }

        #confirmModal .modal-content {
            width: 300px;
            text-align: center;
        }

        #confirmModal button {
            margin: 10px;
            padding: 8px 20px;
        }

        #confirmYes {
            background: #F8CE08;
            color: white;
            border: none;
            border-radius: 4px;
        }

        #confirmNo {
            background: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
        }

        .outer-container {
            padding: 20px;
            background-color: #f5f7fa;
            min-height: calc(100vh - 60px);
        }

        .dashboard-container {
            display: flex;
            gap: 30px;
            padding: 20px;
            background-color: #f5f7fa;
            min-height: calc(60vh - 60px);
        }

        .dashboard-left {
            flex: 1;
            max-width: 800px;
        }

        .dashboard-right {
            flex: 1;
            max-width: 800px;
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
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

        .total-projects .stat-icon {
            background-color: #e3f2fd;
        }

        .total-employees .stat-icon {
            background-color: #f3e5f5;
        }

        .completed-projects .stat-icon {
            background-color: #e8f5e9;
        }

        .ongoing-projects .stat-icon {
            background-color: #fff3e0;
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

        .workload-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 78%;
        }

        .workload-table-container {
            max-height: 300px;
            /* Set maximum height */
            overflow-y: auto;
            /* Enable vertical scrolling */
            margin-top: 15px;
        }

        .workload-table-container::-webkit-scrollbar {
            width: 8px;
        }

        .workload-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .workload-table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .workload-table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Make table header sticky */
        .workload-table thead {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }

        .workload-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .workload-table th {
            background-color: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
        }

        .workload-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .projects-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .projects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .add-project-btn {
            background-color: #F8CE08;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        .add-project-btn:hover {
            background-color: #45a049;
        }

        .projects-table-container {
            overflow-x: auto;
        }

        .projects-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .projects-table thead th {
            background-color: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
        }

        .projects-table tbody tr {
            transition: background-color 0.2s;
        }

        .projects-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .projects-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .project-name {
            font-weight: 500;
            color: #2c3e50;
        }

        .team-leader-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .leader-name {
            color: #444;
        }

        .status-badge,
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        /* Status Badge Colors */
        .status-badge.not-started {
            background-color: #f1f5f9;
            color: #475569;
        }

        .status-badge.in-progress {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .status-badge.completed {
            background-color: #dcfce7;
            color: #166534;
        }

        /* Priority Badge Colors */
        .priority-badge.low {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .priority-badge.medium {
            background-color: #fef3c7;
            color: #92400e;
        }

        .priority-badge.high {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.2s;
            background-color: #f8fafc;
            color: #2c3e50;
        }

        .action-btn:hover {
            background-color: #e2e8f0;
        }

        .view-btn {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .edit-btn {
            background-color: #fef3c7;
            color: #92400e;
        }

        .add-task-btn {
            background-color: #dcfce7;
            color: #166534;
        }

        .add-employee-btn {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        .delete-btn {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .view-btn:hover {
            background-color: #bae6fd;
        }

        .edit-btn:hover {
            background-color: #fde68a;
        }

        .add-task-btn:hover {
            background-color: #bbf7d0;
        }

        .add-employee-btn:hover {
            background-color: #e9d5ff;
        }

        .delete-btn:hover {
            background-color: #fecaca;
        }

        .dashboard-bottom {
            grid-column: 1 / -1;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin: 0;
        }

        /* .assigned-tasks-section {
            height: 100%;
        } */

        .section-header {
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #2c3e50;
            margin: 0;
        }

        .tasks-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .task-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .task-project {
            font-size: 0.9em;
        }

        .project-name {
            color: #EDC716;
            font-weight: 500;
        }

        .task-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .task-status.not-started {
            background-color: #f1f5f9;
            color: #475569;
        }

        .task-status.in-progress {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .task-status.completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .task-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #1e293b;
            margin: 10px 0;
        }

        .task-details {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 0.9em;
        }

        .task-detail-modal {
            background: white;
            width: 90%;
            max-width: 700px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .task-detail-header {
            margin-bottom: 30px;
        }

        .task-title-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .task-title-section h2 {
            margin: 0;
            font-size: 28px;
            color: #2c3e50;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.in-progress {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-badge.completed {
            background: #e8f5e9;
            color: #388e3c;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            width: 120px;
            font-weight: 600;
            color: #546e7a;
        }

        .detail-value {
            color: #2c3e50;
        }

        .description-section {
            margin-bottom: 30px;
        }

        .description-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .description-section p {
            line-height: 1.6;
            color: #37474f;
        }

        .additional-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-bottom: 10px;
            position: relative;
        }

        .progress-indicator {
            position: absolute;
            height: 100%;
            background: #4caf50;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            color: #757575;
            font-size: 14px;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <div id="viewProjectModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <h2>Project Details: <span id="projectTitle"></span></h2>
            <p class="team-leader">Team Leader: <span id="teamLeaderName"></span></p>

            <!-- Rest of your modal content -->
            <div class="section">
                <h3>People Working</h3>
                <div id="employeeList"></div>
            </div>

            <!-- Task Distribution Chart -->
            <div class="section">
                <h3>Task Distribution</h3>
                <canvas id="pieChart"></canvas>
            </div>

            <!-- Task Completion Graph -->
            <div class="section">
                <h3>Task Completion Status</h3>
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
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

    <div id="kanbanModal" class="modal">
        <div class="modal-content kanban-modal-content">
            <button class="close-btn">&times;</button>
            <h2>Task Distribution Board</h2>

            <div class="filter-section">
                <label>Select columns to display:</label><br />
                <select id="attributeFilterKanban" multiple>
                    <option value="id">ID</option>
                    <option value="title" selected>Title</option>
                    <option value="description">Description</option>
                    <option value="status" selected>Status</option>
                    <option value="deadline" selected>Deadline</option>
                    <option value="priority" selected>Priority</option>
                    <option value="created_by">Created By</option>
                </select>
                <button id="applyFiltersKanbanBtn">Apply Kanban Filters</button>
            </div>
            <button id="rearrangeBtn">Toggle Drag & Drop</button>
            <div id="kanbanBoard"></div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to DELETE this project?</p>
            <button id="confirmYes">Yes</button>
            <button id="confirmNo">Cancel</button>
        </div>
    </div>

    <div id="taskDetailModal" class="modal">
        <div class="modal-content task-detail-modal">
            <button class="close-btn" onclick="closeTaskModal()">&times;</button>
            <div id="modalTaskContent" class="task-detail-view">
                <div class="task-detail-header">
                    <div class="task-title-section">
                        <h2 id="modalTaskTitle"></h2>
                        <span id="modalTaskStatus" class="status-badge"></span>
                    </div>
                </div>

                <div class="task-detail-body">
                    <div class="detail-section">
                        <div class="detail-row">
                            <span class="detail-label">Project:</span>
                            <span id="modalProjectName" class="detail-value"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Assigned to:</span>
                            <span id="modalAssignee" class="detail-value"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Deadline:</span>
                            <span id="modalDeadline" class="detail-value"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Priority:</span>
                            <span id="modalPriority" class="detail-value"></span>
                        </div>
                    </div>

                    <div class="description-section">
                        <h3>Description</h3>
                        <p id="modalDescription"></p>
                    </div>

                    <div class="additional-info">
                        <h3>Task Progress</h3>
                        <div class="progress-bar">
                            <div id="progressIndicator" class="progress-indicator"></div>
                        </div>
                        <div class="progress-labels">
                            <span>Not Started</span>
                            <span>In Progress</span>
                            <span>Completed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>

</body>

</html>