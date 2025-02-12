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
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

    <div class="dashboard">
        <div class="overview">
            <div class="card">🏆 Total Projects: <?php echo $project_count; ?></div>
            <div class="card">👥 Total Employees: <?php echo $employee_count; ?></div>
            <div class="card">✅ Completed Projects: <?php echo $completed_projects; ?></div>
            <div class="card">⏳ Ongoing Projects: <?php echo $ongoing_projects; ?></div>
        </div>
        <h2>Team Workload Overview</h2>
        <table>
            <tr>
                <th>Employee</th>
                <th>Assigned Tasks</th>
                <th>Completed</th>
                <th>Remaining</th>
            </tr>
            <?php while ($row = $workload_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["assigned_tasks"]); ?></td>
                    <td><?php echo htmlspecialchars($row["completed_tasks"]); ?></td>
                    <td><?php echo htmlspecialchars($row["assigned_tasks"] - $row["completed_tasks"]); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <h2>Active Projects</h2>
        <table>
            <tr>
                <th>Project</th>
                <th>Team Leader</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $projects->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["title"]); ?></td>
                    <td><?php echo htmlspecialchars($row["leader"]); ?></td>
                    <td><?php echo htmlspecialchars($row["status"]); ?></td>
                    <td><?php echo htmlspecialchars($row["priority"]); ?></td>
                    <td>
                        <button>🔍 View</button>
                        <button>🔄 Edit</button>
                        <button class="delete-btn" data-project-id="<?php echo $row['id']; ?>">🗑️ Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <!-- "Add Project" Button -->
        <button id="openModal">➕ Add Project</button>

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
                                displayEmployeeList(projectData.employees);
                                createPieChart(projectData.employees);
                                createBarChart(projectData.employees);
                                modal.style.display = 'block';
                            }
                        };
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                // View button functionality (keep your existing view button code)

                // Add this for modal close functionality
                const viewModal = document.getElementById('viewProjectModal');
                const closeBtn = viewModal.querySelector('.close');

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

            .close {
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
        </style>

        <div id="viewProjectModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Project Details: <span id="projectTitle"></span></h2>

                <!-- People Working Section -->
                <div class="section">
                    <h3>Employees Assigned</h3>
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
</body>

</html>