{block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_test_data"}
    <div id="heidelpay--hire-purchase-test-data">
        {block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_test_data_title"}
            <p>{s name='title'}{/s}</p>
        {/block}
        {block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_test_data_table"}
            <table class="heidelpay--test-data-table">
                {block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_test_data_table_rows"}
                    <tr>
                        <th><p>{s name='column/accountHolder'}{/s}</p></th>
                        <th><p>{s name='column/iban'}{/s}</p></th>
                        <th><p>{s name='column/bic'}{/s}</p></th>
                        <th><p>{s name='column/birthday'}{/s}</p></th>
                    </tr>
                    <tr>
                        <td>Manuel Weißmann</td>
                        <td>DE89370400440532013000</td>
                        <td>COBADEFFXXX</td>
                        <td>03.10.1979</td>
                    </tr>
                {/block}
            </table>
        {/block}
    </div>
{/block}
