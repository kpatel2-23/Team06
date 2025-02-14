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
                                <td><span class="badge"><?php echo htmlspecialchars($row["assigned_tasks"]); ?></span></td>
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
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add Project</h2>
            <form id="addProjectForm">
                <label>Title:</label>
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

                <label>Team Leader:</label>
                <select id="team_leader" name="team_leader" class="select2" required></select>

                <label>Assign Employees:</label>
                <select id="employees" name="employees[]" class="select2" multiple required></select>

                <button type="submit">Add Project</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function () {
                    let projectId = this.getAttribute("data-project-id");

                    if (confirm("Are you sure you want to delete this project? This action cannot be undone.")) {
                        fetch("delete_project.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "project_id=" + projectId
                        })
                            .then(response => response.text())
                            .then(result => {
                                if (result === "success") {
                                    alert("Project deleted successfully!");
                                    this.closest("tr").remove(); // Remove the project row from UI
                                } else {
                                    alert("Error deleting project: " + result);
                                }
                            });
                    }
                });
            });
        });


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
                .then(response => response.text())
                .then(result => {
                    alert(result);
                    location.reload();
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

                        modal.style.display = 'block';
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading employees');
                    }
                });
            });

            // Add Employee Modal close functionality
            const addEmployeeModal = document.getElementById('addEmployeeModal');
            if (addEmployeeModal) {
                const closeBtn = addEmployeeModal.querySelector('.close-btn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        addEmployeeModal.style.display = 'none';
                    });
                }

                // Close on outside click
                window.addEventListener('click', function (event) {
                    if (event.target === addEmployeeModal) {
                        addEmployeeModal.style.display = 'none';
                    }
                });
            }
        });
        // Handle employee assignment form submission
        $(document).ready(function () {
            $("#assignEmployeesForm").submit(function (e) {
                e.preventDefault();

                // Get project ID from the hidden input inside the modal
                let selectedProjectId = $("#employeeProjectId").val();
                let selectedEmployees = $("input[name='employee_ids[]']:checked").map(function () {
                    return $(this).val();
                }).get(); // Collect selected employees as an array

                if (!selectedProjectId || selectedEmployees.length === 0) {
                    alert("Please select at least one employee.");
                    return;
                }

                $.ajax({
                    url: "assign_employees.php",
                    type: "POST",
                    data: { project_id: selectedProjectId, employee_ids: selectedEmployees },
                    success: function (response) {
                        let data = JSON.parse(response);
                        if (data.success) {
                            alert("Employees added successfully!");
                            loadProjectEmployees(selectedProjectId); // Refresh employees dropdown
                        } else {
                            alert("Failed: " + data.message);
                        }
                    }
                });
            });
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

        document.getElementById('addTaskForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Debug: Log all form data
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            try {
                const response = await fetch('add_task.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                console.log('Server response:', result); // Debug: Log server response

                if (result.includes('success')) {
                    document.getElementById('taskModal').style.display = 'none';
                    location.reload();
                } else {
                    alert(result);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error creating task');
            }
        });







    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            width: 400px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .modal-content {
            width: 800px;
            max-height: 90vh;
            overflow-y: auto;
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
            background-color: #4CAF50;
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
            background: #4CAF50;
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
            background: #4CAF50;
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

        .dashboard-container {
            display: flex;
            gap: 30px;
            padding: 20px;
            background-color: #f5f7fa;
            min-height: calc(100vh - 60px);
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
        }

        .workload-table-container {
            overflow-x: auto;
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
            background-color: #4CAF50;
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
                <select id="taskEmployees" name="employees[]" multiple required class="select2"></select>

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
            <p>Are you sure you want to move this task?</p>
            <button id="confirmYes">Yes</button>
            <button id="confirmNo">No</button>
        </div>
    </div>
</body>

</html>