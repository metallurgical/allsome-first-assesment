<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allsome First Assessment</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">

<div class="w-full max-w-2xl">
    <div class="bg-white rounded-2xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Assessment to convert CSV → JSON</h1>
        <p class="text-sm text-gray-500 mb-6">
            Choose custom .csv file, or leave blank for default
            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">allsome_interview_test_orders.csv</span>.
        </p>

        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
            <div id="uploadArea"
                class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-indigo-400 transition-colors cursor-pointer">
                <input type="file" id="csv" name="csv" class="hidden">
                <svg class="mx-auto mb-3 w-9 h-9 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span id="fileLabel" class="text-sm text-gray-500">
                    Click to upload Your CSV file
                </span>
                <!-- Sengaja comment to let backend handle validation -->
                <!-- <p class="text-xs text-gray-400 mt-1">Only .csv files are accepted</p> -->
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold py-2.5 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                Proses CSV File
            </button>
        </form>

        <!-- JSON result -->
        <div id="result" class="hidden mt-6">
            <div class="mb-2">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-widest">JSON Output</h2>
            </div>
            <pre id="jsonOutput"
                class="bg-gray-900 text-green-400 font-mono text-sm rounded-xl p-5 overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
        </div>

        <!-- Error box to show error from backend-->
        <div id="errorBox"
            class="hidden mt-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm-.75-5.25a.75.75 0 001.5 0v-3a.75.75 0 00-1.5 0v3zm.75-6a.75.75 0 100 1.5.75.75 0 000-1.5z"
                    clip-rule="evenodd"/>
            </svg>
            <span id="errorText"></span>
        </div>
    </div>
    <p class="text-center text-xs text-gray-400 mt-4">
        Made by <a href="https://github.com/metallurgical" class="hover:underline">Norlihazmey</a>
    </p>
</div>

<script>
$(function () {
    // Trigger hidden file input
    $('#uploadArea').on('click', () => $('#csv')[0].click());

    // Update the label to show the choosen filename, or reset not selected
    $('#csv').on('change', function () {
        var that = this;
        $('#fileLabel').text(that.files[0] ? that.files[0].name : 'Click to upload a CSV file');
    });

    // Hwere will handle submitted form
    $('#uploadForm').on('submit', function (e) {
        // Prevent default browser form submission
        e.preventDefault();
        const $btn = $(this).find('[type=submit]');

        // Hide previous result or error
        $('#result, #errorBox').addClass('hidden');
        // Disable button and show perkataan "processing"
        $btn.text('Processing…').prop('disabled', true);

        $.ajax({
            url: 'process.php',
            method: 'POST',
            // Send form data as multipart so the file is sent together
            data: new FormData(this),
            // Let the browser set the correct Content-Type boundary
            processData: false,
            contentType: false,
            success(data) {
                // Show JSON response and show the result section
                $('#jsonOutput').text(JSON.stringify(data, null, 4));
                $('#result').removeClass('hidden');
            },
            error(xhr) {
                // Show the error message returned by the server
                $('#errorText').text(xhr.responseJSON?.error ?? 'An unknown error occurred.');
                $('#errorBox').removeClass('hidden');
            },
            complete() {
                // Re-enable the button regardless of success or failure
                $btn.text('Process CSV').prop('disabled', false);
            },
        });
    });
});
</script>

</body>
</html>
