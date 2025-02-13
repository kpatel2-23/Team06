<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Project Tree & Kanban Board</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
<div class="container">
  <div class="split">
    <div id="projectTreeContainer" class="left-pane">
      <h2>Project Tree</h2>
      <div class="filter-section">
        <label>Select columns to display:</label><br/>
        <select id="attributeFilterTree" multiple>
          <option value="id">ID</option>
          <option value="title" selected>Title</option>
          <option value="description" selected>Description</option>
          <option value="status" selected>Status</option>
          <option value="deadline" selected>Deadline</option>
          <option value="priority" selected>Priority</option>
          <option value="created_by">Created By</option>
        </select>
        <button id="applyFiltersTreeBtn">Apply Tree Filters</button>
      </div>
      <svg id="projectTreeSVG" width="600" height="800"></svg>
    </div>
    <div id="kanbanContainer" class="right-pane">
      <h2>Kanban Board</h2>
      <div class="filter-section">
        <label>Select columns to display:</label><br/>
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
      <button id="rearrangeBtn">Rearrange</button>
      <div id="kanbanBoard"></div>
    </div>
  </div>
</div>
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to move this task?</p>
    <button id="confirmYes">Yes</button>
    <button id="confirmNo">No</button>
  </div>
</div>
<script src="script.js"></script>
</body>
</html>
