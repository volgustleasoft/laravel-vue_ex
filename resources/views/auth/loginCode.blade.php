<x-layout>
    <section class="signup-form">
        <div class="wrap">
            <div class="card signup signup-small">
                <h3>Verificatie</h3>
                <p>De pincode is verstuurd via SMS. Vul deze code hieronder in om in te loggen.</p>
                <form method="post" action="/login/code">
                    @csrf
                    <input type="hidden" name="PhoneNumber" value="{{ $PhoneNumber }}" />
                    <input type="hidden" name="CodeInput" value="1" />
                    <div class="input-group @if(! empty($error))error @endif">
                        <div class="with-lt-icon">
                            <input type="text" placeholder="SMS Code" id="code" name="Code" autocomplete="off" oninput="checkLength();" />
                            <i>vpn_key</i>
                        </div>
                        @if(! empty($error))
                            <div class="error-message">{{ $error }}</div>
                        @endif
                    </div>
                    <div class="buttons-group">
                        <button type="submit" class="button disabled" id="submitCodeButton">Inloggen</button>
                    </div>
                </form>
                <div class="footer">
                    Het kan een minuut duren voordat je het smsje ontvangt.
                </div>
            </div>

        </div>
    </section>
</x-layout>

<script>
    function checkLength() {
        var value = document.getElementById("code").value;

        if (value.length > 0) {
            document.getElementById("submitCodeButton").classList.remove("disabled");
        } else {
            document.getElementById("submitCodeButton").classList.add("disabled");
        }
    }
</script>
