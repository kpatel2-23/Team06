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

// If a project is clicked, show details
$selected_project_id = isset($_GET["project_id"]) ? $_GET["project_id"] : null;
?>

<?php include("navbar.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="style.css">
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
    <ul>
        <?php foreach ($leader_projects as $project): ?>
            <li>
                <a href="employee.php?project_id=<?php echo $project["id"]; ?>">
                    <strong><?php echo $project["title"]; ?></strong>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>My Tasks</h2>
    <ul>
        <?php while ($task = $tasks->fetch_assoc()): ?>
            <li>
                <strong><?php echo $task["title"]; ?></strong> - <?php echo $task["description"]; ?>
                (<?php echo $task["status"]; ?>) - <em>Project: <?php echo $task["project_name"]; ?></em>
            </li>
        <?php endwhile; ?>
    </ul>

    <?php if ($selected_project_id): ?>
        <h2>Tasks for <?php echo $leader_projects[array_search($selected_project_id, array_column($leader_projects, 'id'))]["title"]; ?></h2>
        <button id="openTaskModal">➕ Create Task for This Project</button>

        <div id="taskModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Create Task for <?php echo $leader_projects[array_search($selected_project_id, array_column($leader_projects, 'id'))]["title"]; ?></h2>
                <form id="addTaskForm">
                    <input type="hidden" name="project_id" value="<?php echo $selected_project_id; ?>">

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
    <?php endif; ?>

    <style>
        .modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 1000; }
        .modal-content { position: relative; width: 400px; }
        .close { position: absolute; top: 10px; right: 10px; cursor: pointer; }
    </style>

    <script>
        document.getElementById("openTaskModal").onclick = function() {
            document.getElementById("taskModal").style.display = "block";
        };

        document.querySelector(".close").onclick = function() {
            document.getElementById("taskModal").style.display = "none";
        };

        // Fetch employees dynamically
        fetch("fetch_employees.php")
            .then(response => response.json())
            .then(data => {
                let employeesDropdown = document.getElementById("taskEmployees");
                data.forEach(emp => {
                    let option = `<option value="${emp.id}">${emp.name}</option>`;
                    employeesDropdown.innerHTML += option;
                });
            });

        // Submit Task Form
        document.getElementById("addTaskForm").onsubmit = function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);

            fetch("add_task.php", {
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
</body>
</html>
