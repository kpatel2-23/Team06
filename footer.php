<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo date("Y"); ?> Make-it-all. All Rights Reserved.</p>
        <ul class="footer-links">
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="contact.php">Contact Us</a></li>
        </ul>
    </div>
</footer>

<style>
    .footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 5px 0;
        font-family: 'Poppins', sans-serif;
        border-top: 2px solid #444;
        position: relative;
        bottom: 0;
        width: 100%;
        border-radius: 10px;
        margin-top: 20px;
    }

    .footer-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0px; /* Controls spacing between text and links */
    }

    .footer-links {
        list-style: none;
        padding: 0;
        display: flex;
        gap: 15px;
        margin-top: 5px;
    }

    .footer-links li {
        display: inline;
    }

    .footer-links a {
        color: #ddd;
        text-decoration: none;
        font-size: 14px;
    }

    .footer-links a:hover {
        color: #f2f2f2;
        text-decoration: underline;
    }
</style>