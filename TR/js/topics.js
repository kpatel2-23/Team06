function searchTopics() {
    let query = document.getElementById('search').value;
    if (query.length > 2) {
        fetch(`topics_functions.php?search=${query}`)
            .then(response => response.json())
            .then(data => {
                let results = document.getElementById('searchResults');
                results.innerHTML = "";
                data.forEach(topic => {
                    results.innerHTML += `<p><strong>${topic.title}</strong> - ${topic.category}<br>${topic.description}</p>`;
                });
            });
    }
}
