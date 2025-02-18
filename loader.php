<div id="loader">
    <div class="spinner"></div>
</div>

<style>
    /* Loader Styles */
    #loader {
        position: fixed;
        width: 100%;
        height: 100vh;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.3s ease-in-out;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #F8CE08;
        border-top: 5px solid transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        setTimeout(() => {
            document.getElementById("loader").style.opacity = "0";
            setTimeout(() => document.getElementById("loader").style.display = "none", 300);
        }, 1000);
    });
</script>

