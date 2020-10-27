<div style="border: 1px solid grey; padding: 16px; border-radius: 2px; margin-top: 16px">
    <h3>Überweisungsinformationen</h3>
    <div>Bitte überweisen Sie uns den Rechnungsbetrag auf folgendes Konto:</div>
    <table style="border: 1px solid grey; margin-top: 16px; width: 100%">
        <tr>
            <td style="background-color: lightgray; width: 20%; padding-left: 8px">Betrag</td>
            <td style="padding-left: 8px">{$bankData.amount|currency}</td>
        </tr>
        <tr>
            <td style="background-color: lightgray; padding-left: 8px">Empfänger</td>
            <td style="padding-left: 8px">{$bankData.holder}</td>
        </tr>
        <tr>
            <td style="background-color: lightgray; padding-left: 8px">IBAN</td>
            <td style="padding-left: 8px">{$bankData.iban}</td>
        </tr>
        <tr>
            <td style="background-color: lightgray; padding-left: 8px">BIC</td>
            <td style="padding-left: 8px">{$bankData.bic}</td>
        </tr>
        <tr>
            <td style="background-color: lightgray; padding-left: 8px">Verwendungszweck</td>
            <td style="padding-left: 8px">{$bankData.descriptor}</td>
        </tr>
    </table>
</div>
