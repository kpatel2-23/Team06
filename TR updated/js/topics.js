document.addEventListener("DOMContentLoaded", function () {
    let searchInput = document.getElementById('search');

    searchInput.addEventListener("keyup", function () {
        let query = searchInput.value.trim();
        if (query.length > 2) {
            fetch(`topics_functions.php?search=${query}`)
                .then(response => response.json())
                .then(data => {
                    let results = document.getElementById('searchResults');
                    results.innerHTML = "";
                    if (data.length === 0) {
                        results.innerHTML = "<p>No topics found.</p>";
                    } else {
                        data.forEach(topic => {
                            results.innerHTML += `
                                <p>
                                    <a href="topic.php?topic_id=${topic.id}">
                                        <strong>${topic.title}</strong>
                                    </a> - ${topic.category}<br>${topic.description}
                                </p>
                            `;
                        });
                    }
                })
                .catch(error => console.error('Error fetching search results:', error));
        }
    });
});
