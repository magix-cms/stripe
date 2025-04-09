{if !isset($id)}{$id = 1}{/if}
{if !isset($custom_mail)}{$custom_mail = false}{/if}
<form id="{$id}_directpay-form" method="post" class="contact-form validate_form" action="{$url}/{$lang}/stripe/">
    {*<div class="row">
        <div class="col-12 col-xs-6">
            <div class="form-group">
                <input type="number" min="1" step="1" name="quantity" id="quantity" class="form-control required" value="1" required/>
                <label for="quantity" class="is_empty">{#quantity#|ucfirst} *</label>
            </div>
        </div>
    </div>*}
    {*<pre>{print_r(json_decode($shipping))}</pre>*}
    <div class="row">
        <div class="col-12 col-xs-6">
            <div class="form-group">
                <label for="{$id}_firstname" class="is_empty">{#pn_contact_firstname#|ucfirst}*&nbsp;:</label>
                <input id="{$id}_firstname" type="text" name="custom[firstname]" placeholder="{#ph_contact_firstname#|ucfirst}" class="form-control required" required/>
            </div>
        </div>
        <div class="col-12 col-xs-6">
            <div class="form-group">
                <label for="{$id}_lastname" class="is_empty">{#pn_contact_lastname#|ucfirst}*&nbsp;:</label>
                <input id="{$id}_lastname" type="text" name="custom[lastname]" placeholder="{#ph_contact_lastname#|ucfirst}" class="form-control required" required/>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-xs-6 col-sm-12 col-md-6">
            <div class="form-group">
                <label for="{$id}_email" class="is_empty">{#pn_contact_mail#|ucfirst}*&nbsp;:</label>
                <input id="{$id}_email" type="email" name="custom[email]" placeholder="{#ph_contact_mail#|ucfirst}" class="form-control required" required/>
            </div>
        </div>
        <div class="col-12 col-xs-6 col-sm-12 col-md-3">
            <div class="form-group">
                <label for="{$id}_phone" class="is_empty">{#pn_contact_phone#|ucfirst}&nbsp;:</label>
                <input id="{$id}_phone" type="tel" name="custom[phone]" placeholder="{#ph_contact_phone#|ucfirst}" class="form-control phone" pattern="{literal}^((?=[0-9\+ \(\)-]{9,20})(\+)?\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,3}(-| )?\d{1,3}(-| )?\d{1,3}(-| )?\d{1,3})${/literal}" maxlength="20" />
            </div>
        </div>
        <div class="col-12 col-xs-6 col-sm-12 col-md-3">
            <div class="form-group">
                <label for="{$id}_vat" class="is_empty">{#pn_contact_vat#|ucfirst}&nbsp;:</label>
                <input id="{$id}_vat" type="text" name="custom[vat]" placeholder="{#ph_contact_vat#|ucfirst}" class="form-control" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="{$id}_address" class="is_empty">{#pn_contact_address#|ucfirst}{if $contact.address_required}*{/if}&nbsp;:</label>
                <input id="{$id}_address" type="text" name="custom[address]" placeholder="{#ph_address#|ucfirst}" value="" class="form-control{if $contact.address_required} required{/if}" {if $contact.address_required}required{/if}/>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="form-group">
                <label for="{$id}_postcode" class="is_empty">{#pn_contact_postcode#|ucfirst}{if $contact.address_required}*{/if}&nbsp;:</label>
                <input id="{$id}_postcode" type="text" name="custom[postcode]" placeholder="{#ph_postcode#|ucfirst}" value="" class="form-control{if $contact.address_required} required{/if}" {if $contact.address_required}required{/if}/>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="form-group">
                <label for="{$id}_city" class="is_empty">{#pn_contact_city#|ucfirst}{if $contact.address_required}*{/if}&nbsp;:</label>
                <input id="{$id}_city" type="text" name="custom[city]" placeholder="{#ph_city#|ucfirst}" value="" class="form-control{if $contact.address_required} required{/if}" {if $contact.address_required}required{/if}/>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <div class="form-group">
                {country_data}
                <label for="country" class="is_empty">{#country#|ucfirst}&nbsp;:</label>
                <select name="custom[country]" id="country" class="form-control">
                    {*<option disabled selected>-- {#pn_transport_country#|ucfirst} --</option>*}
                    {foreach $countries as $country}
                        <option value="{$country.iso}">{#$country.name#}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="{$id}_content" class="is_empty">{#pn_contact_message#|ucfirst}*&nbsp;:</label>
        <textarea id="{$id}_content" name="custom[content]" rows="2" class="form-control"></textarea>
    </div>
    {*{include file="recaptcha/form/recaptcha.tpl" action="contact"}*}
    <div class="mc-message"></div>
    <p id="{$id}_btn-contact" class="text-center">
        <input type="hidden" name="custom[productprice]" id="productprice" value="" />
        <input type="hidden" name="custom[weightprice]" id="weightprice" value=""/>
        <input type="hidden" name="custom[quantity]" id="quantity" value="" />
        <input type="hidden" name="purchase[amount]" id="total" value=""/>
        <input type="hidden" name="redirect" id="redirect">
        <input type="hidden" name="custom[product]" value="{$product.name}" />
        <input type="hidden" name="custom[ref]" value="{$product.reference}" />
        <button type="submit" class="btn btn-main">{#btn_buy_this_product#|ucfirst}</button>
        {if $custom_mail}<input type="hidden" name="custom[custom_mail]" class="required" value="{$custom_mail}" required/>{/if}
    </p>
</form>