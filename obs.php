<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Shift Record</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 30px auto;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input,
        textarea,
        button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        button {
            margin-top: 20px;
            cursor: pointer;
        }

        #response {
            margin-top: 20px;
            white-space: pre-wrap;
            background: #f4f4f4;
            padding: 10px;
        }
    </style>
</head>

<body>

    <h2>Daily Shift Record</h2>

    <form id="shiftForm">

        <label>Care Call ID *</label>
        <input type="text" name="col_care_call_Id" value="1" required>

        <label>Shift End Time</label>
        <input type="datetime-local" name="shift_end_time" value="2025-12-25T18:30">

        <label>Call Status</label>
        <input type="text" name="col_call_status" value="completed">

        <label>Worked Time</label>
        <input type="text" name="col_worked_time" value="02:30">

        <label>Care Call Rate</label>
        <input type="number" step="0.01" name="col_carecall_rate" value="15.50">

        <label>Client Rate</label>
        <input type="number" step="0.01" name="col_client_rate" value="22.00">

        <label>Task Note</label>
        <textarea name="task_note">Client assisted with medication and meal preparation.</textarea>

        <button type="submit">Submit</button>
    </form>

    <pre id="response"></pre>

    <script>
        document.getElementById('shiftForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const responseBox = document.getElementById('response');
            responseBox.textContent = 'Submitting...';

            try {
                const response = await fetch('update_daily_shift.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                responseBox.textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                responseBox.textContent = 'Error: ' + error.message;
            }
        });
    </script>

</body>

</html>