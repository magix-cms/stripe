{extends file="layout.tpl"}
{block name='head:title'}stripe{/block}
{block name='body:id'}stripe{/block}
{block name='article:header'}
    <h1 class="h2">stripe</h1>
{/block}
{block name='article:content'}
    {if {employee_access type="view" class_name=$cClass} eq 1}
        <div class="panels row">
            <section class="panel col-ph-12">
                {if $debug}
                    {$debug}
                {/if}
                <header class="panel-header">
                    <h2 class="panel-heading h5">{#stripe_management#}</h2>
                </header>
                <div class="panel-body panel-body-form">
                    <div class="mc-message-container clearfix">
                        <div class="mc-message"></div>
                    </div>
                    <div class="row">
                        <form id="stripe_config" action="{$smarty.server.SCRIPT_NAME}?controller={$smarty.get.controller}&amp;action=edit" method="post" class="validate_form edit_form col-xs-12 col-md-6">
                            <div class="row">
                                <div class="col-xs-12 col-sm-10">
                                    <div class="form-group">
                                        <label for="apikey">apikey :</label>
                                        <div class="input-group">
                                            <div class="input-group-addon"><span class="fa fa-key"></span></div>
                                            <input type="text" class="form-control" id="apikey" name="apikey" value="{$stripe.apikey}" size="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-10">
                                    <div class="form-group">
                                        <label for="apikey">endpointkey :</label>
                                        <div class="input-group">
                                            <div class="input-group-addon"><span class="fa fa-key"></span></div>
                                            <input type="text" class="form-control" id="endpointkey" name="endpointkey" value="{$stripe.endpointkey}" size="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-10">
                                    <div class="form-group">
                                        <label for="apikey">secret signing key :</label>
                                        <div class="input-group">
                                            <div class="input-group-addon"><span class="fa fa-key"></span></div>
                                            <input type="text" class="form-control" id="secret_signing_key" name="secret_signing_key" value="{$stripe.secret_signing_key}" size="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="submit">
                                <button class="btn btn-main-theme" type="submit" name="action" value="edit">{#save#|ucfirst}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    {else}
        {include file="section/brick/viewperms.tpl"}
    {/if}
{/block}