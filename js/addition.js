document.addEventListener("DOMContentLoaded", function () {
    let usernameInput = document.getElementById("username");

    if (usernameInput) {
        // Function to format the input correctly
        function formatUsernameInput() {
            let value = usernameInput.value;
            let match = value.match(/^(\d+-)(.*)$/); // Match country code followed by '-'

            if (match) {
                let countryCode = match[1]; // Extract country code (e.g., '1-')
                let phoneNumber = match[2]; // Extract actual phone number

                // Store country code in a data attribute
                usernameInput.dataset.prefix = countryCode;

                // Update the input display
                usernameInput.value = phoneNumber;
            }
        }

        // Run formatting when the input changes
        usernameInput.addEventListener("input", formatUsernameInput);

        // Run formatting on page load in case the input already has a value
        formatUsernameInput();
    }
});
