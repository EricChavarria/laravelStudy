@extends('layouts.app')

@section('content')
    <head>
            <meta charset="UTF-8">
            <title>Loans</title>
    </head>
    <script>

        let loans = [];

        function addNewLoan() {
            loans.push({
                id: document.getElementById("name").value,
                principle: document.getElementById("principle").value,
                rate: document.getElementById("rate").value
            });
            updateLoans();
            clearNewLoanForm();
        }

        function updateLoans() {
            var html = ""

            loans.forEach(loan => {
                html += `<li>${loan.id}: $${loan.principle} @ ${loan.rate}%</li>`
            });

            document.getElementById("loans").innerHTML = html;
        }

        function clearNewLoanForm() {
            document.getElementById("name").value = "";
            document.getElementById("principle").value = "";
            document.getElementById("rate").value = "";
        }

        function sendForm() {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if(xhr.readyState === 4)
                document.body.innerText = xhr.response;
            };
            xhr.open("POST", "loans.php", true);
            xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
            var j = {
                loans: loans,
                extra: document.getElementById("extra").value
            };
            xhr.send(JSON.stringify(j));
        }

    </script>
    <body>
        <div>
            Loans:<br/>
            <ul id="loans">
                <!--Loans Appear here-->
            </ul><br/>
            New Loan:<br/>
            <label for="name">Name: <input id="name" type="text"/></label><br/>
            <label for="principle">Principle: <input id="principle" type="number"/></label><br/>
            <label for="rate">Interest rate: <input id="rate" type="text"/></label><br/>
            <br/>
            <input type="button" onclick="addNewLoan()" value="Add Another Loan"><br/>
            <br/>
            <label for="rate">Extra Money this Month: <input id="extra" type="number"/></label><br/>
            <br/>
            <input type="button" onclick="sendForm()" value="Submit">

        </div>
    </body>
@endsection
