<x-layout>
    <section class="signup-form">
        <div class="wrap">
            <div class="card signup">
                <h3>Inloggen</h3>
                <form method="post" onSubmit="document.getElementById('submitPhoneButton').disabled=true;" action="/login">
                    @csrf
                    <div class="input-group">
                        <div class="with-lt-icon">
                            <input type="text" id="PhoneNumber" name="PhoneNumber" placeholder="Telefoonnummer" oninput="checkLength();" />
                            <i>phone_android</i>
                        </div>
                    </div>
                    @if(! empty($error))
                        <div class="error-message">{{ $error }}</div>
                    @endif
                    <div class="buttons-group">
                        <button type="submit" class="button disabled" id="submitPhoneButton">Inloggen Mijntest</button>
                    </div>
                </form>
                <div class="footer">
                    Als je op ‘Inloggen Mijntest’ klikt, ontvang je een sms met de inlogcode. Deze kan je vervolgens invullen om verder te gaan.
                </div>
            </div>
        </div>
    </section>
    <section class="signup-information">
        <div class="wrap">
            <h3>Wat is Mijntest?</h3>
            <p>Word je cliënt van test Overijssel? Dan kun je met onze online zorg app Mijntest zelf de regie houden over de zorg die jij nodig hebt. Dat doe je door bijvoorbeeld zelf een afspraak in te plannen via de app. Je kunt daarmee gemakkelijk en snel een zorgprofessional inschakelen, maar ook kun je met de app je vrienden, familie en netwerk vragen om ondersteuning.</p>
            <p>
                Gebruik maken van de app kan dus alleen als je cliënt van ons bent. Je aanmelden voor ondersteuning van test doe je via het aanmeldformulier op
                <a class="orange" href="https://testoverijssel.nl" target="_blank">testoverijssel.nl</a>.
            </p>
            <div class="with-lt-icon">
                <a href="https://www.testoverijssel.nl/aanmelden" target="_blank" class="button alt">Aanmeldformulier</a>
                <i>person_add</i>
            </div>
            <h3>Ben jij al aangemeld voor Mijntest?</h3>
            <p>Heeft jouw begeleider je al aangemeld voor Mijntest? Dan kun je hier gemakkelijk inloggen met jouw telefoonnummer. Ben je nog niet aangemeld? Vraag je begeleider naar de mogelijkheden.</p>
            <h3>Voor welke teams is Mijntest al actief? </h3>
            <ul>
                <li>Enschede Oost</li>
                <li>Raalte</li>
                <li>Deventer</li>
                <li>Almelo Noord</li>
            </ul>
            <p class="expansion">Binnenkort gaan meer teams Mijntest actief gebruiken. We houden je op de hoogte!</p>
            <div class="buttons-group">
                <div class="with-lt-icon">
                    <a href="https://testoverijssel.nl" target="_blank" class="button alt">Terug naar testoverijssel.nl</a>
                    <i>arrow_back</i>
                </div>

                <div class="with-lt-icon">
                    <a href="/login/" class="button alt">Inloggen</a>
                    <i>arrow_upward</i>
                </div>
            </div>
        </div>
    </section>
</x-layout>
    <script>
        function checkLength() {
            var value = document.getElementById("PhoneNumber").value;

            if (value.length >= 10) {
                document.getElementById("submitPhoneButton").classList.remove("disabled");
            } else {
                document.getElementById("submitPhoneButton").classList.add("disabled");
            }
        }
    </script>
