<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-beroo" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1>Інтеграція з Beroo</h1>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <?php if ($success) { ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-beroo" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-name">Ваш домен Beroo</label>
                        <div class="col-sm-10">
                            <input type="text" name="module_beroo_domain" value="<?php echo $module_beroo_domain; ?>" placeholder="Ваш домен Beroo" id="input-domain" class="form-control" />
                            <?php if ($error_domain) { ?>
                            <div class="text-danger"><?php echo $error_domain; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-limit">Ключ форми</label>
                        <div class="col-sm-10">
                            <input type="text" name="module_beroo_key" value="<?php echo $module_beroo_key; ?>" placeholder="Ключ форми" id="input-key" class="form-control" />
                            <?php if ($error_key) { ?>
                            <div class="text-danger"><?php echo $error_key; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status">Статус</label>
                        <div class="col-sm-10">
                            <select name="module_beroo_status" id="input-status" class="form-control">
                                <option value="1" <?php if($module_beroo_status){ echo 'selected="selected"'; } ?>>Увімкнено</option>
                                <option value="0" <?php if(!$module_beroo_status){ echo 'selected="selected"'; } ?>>Вимкнено</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status">Прив'язка товару</label>
                        <div class="col-sm-10">
							<input type="radio" id="module_beroo_product_bind_id" name="module_beroo_product_bind" value="id" <?php if($module_beroo_product_bind == 'id'){ echo 'checked'; }?>> <label style="font-weight: normal; vertical-align: middle;" for="module_beroo_product_bind_id">ID (рекомендовано)</label><br>
							<input type="radio" id="module_beroo_product_bind_model" name="module_beroo_product_bind" value="model"  <?php if($module_beroo_product_bind == 'model'){ echo 'checked'; }?>> <label style="font-weight: normal; vertical-align: middle;" for="module_beroo_product_bind_model">Модель</label><br>
							<input type="radio" id="module_beroo_product_bind_sku" name="module_beroo_product_bind" value="sku"  <?php if($module_beroo_product_bind == 'sku'){ echo 'checked'; }?>> <label style="font-weight: normal; vertical-align: middle;" for="module_beroo_product_bind_sku">SKU</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status">Мова товарів</label>
                        <div class="col-sm-10">
                            <select name="module_beroo_product_language" id="input-status" class="form-control">
							<?php foreach($languages as $language){ ?>
                                <option value="<?php echo $language['language_id']; ?>" <?php if($language['language_id']==$module_beroo_product_language){ echo 'selected="selected"'; } ?>><?php echo $language['name']; ?></option>
							<? } ?>
                            </select>
						</div>
                    </div>
                    
                    <?php if (!empty($module_beroo_domain) && !empty($module_beroo_key)) { ?>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-status">Зіставлення способів оплати</label>
							<div class="col-sm-10">
								<?php  if (!empty($beroo_payment_methods_error)) { ?>
								<div class="alert alert-danger"><?php echo htmlspecialchars($beroo_payment_methods_error); ?></div>
								<?php } ?>
								<table class="table" style="width: auto; margin-bottom: 0;">
								<thead>
									<tr>
										<th width="250">В OpenCart</th>
										<th width="250">В Beroo</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($payment_methods as $payment_method){ ?>
									<tr>
										<td><?php echo $payment_method['name']; ?></td>
										<td><select name="module_beroo_match_payment_methods[<?php echo $payment_method['code']; ?>]" style="width:100%">
											<option>---</option>
											<?php foreach($beroo_payment_methods as $beroo_payment_method){ ?>
											<option value="<?php echo htmlspecialchars($beroo_payment_method['parameter']); ?>"
											<?php
										  		if(isset($match_payment_methods[$payment_method['code']]) && ($match_payment_methods[$payment_method['code']] == $beroo_payment_method['parameter'])){ echo 'selected'; } 
										  	?>
											><?php echo $beroo_payment_method['name']; ?></option>
											<?php } ?>
										</select></td>
									</tr>
									<?php } ?>
								<tbody>
								</table>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-status">Зіставлення способів доставки</label>
							<div class="col-sm-10">
								<?php  if (!empty($beroo_shipping_methods_error)) { ?>
								<div class="alert alert-danger"><?php echo htmlspecialchars($beroo_shipping_methods_error); ?></div>
								<?php } ?>
								<table class="table" style="width: auto; margin-bottom: 0;">
								<thead>
									<tr>
										<th width="250">В OpenCart</th>
										<th width="250">В Beroo</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($shipping_methods as $shipping_method){ ?>
									<tr>
										<td><?php echo $shipping_method['name']; ?></td>
										<td><select name="module_beroo_match_shipping_methods[<?php echo $shipping_method['code']; ?>]" style="width:100%">
											<option>---</option>
											<?php foreach($beroo_shipping_methods as $beroo_shipping_method){ ?>
											<option value="<?php echo htmlspecialchars($beroo_shipping_method['parameter']); ?>"
												<?php 
												if(isset($match_shipping_methods[$shipping_method['code']]) && ($match_shipping_methods[$shipping_method['code']] == $beroo_shipping_method['parameter']))
												{ echo 'selected'; } 
												?>
											><?php echo $beroo_shipping_method['name']; ?></option>
											<?php } ?>
										</select></td>
									</tr>
									<?php } ?>
								<tbody>
								</table>
							</div>
						</div>
                  	<?php } ?>
                   
                    <div class="form-group" style="padding-left: 15px;">
                   		<button type="submit" form="form-beroo" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp; Зберегти</button>
                    </div>
                    
                    <?php if (!empty($module_beroo_domain) && !empty($module_beroo_key)) { ?>
                    <div class="form-group" style="padding-left: 15px; border-bottom: 1px solid #ededed;">
                   		<div class="btn btn-warning" id="fca-import-order"><i class="fa fa-refresh"></i>&nbsp; Синхронізувати всі товари</div>
						<script src="view/javascript/beroo/sync.js"></script>
						<link type="text/css" href="view/javascript/beroo/sync.css" rel="stylesheet" media="screen" />
						<div id="importProductsUrl" style="display: none"><?php echo $synchronize; ?></div>
						<div class="fca_ajax_result" style="display: none">Експортовано товарів: <span id="currentOffset">0</span>. Створено товарів з урахуванням варіацій: <span id="variationCount">0</span>. Час виконання: <span id="timeElapsed">0</span>. <span id="sd-finish" style="display: none">ЗАВЕРШЕНО!</span></div>
						<div id="fc_api_project_box">
							<div class="fca_preloader">
								<div>Товари та категорії експортуються. Не закривайте браузер до завершення експорту.</div>
								<div class="lds-default">
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
									<div></div>
								</div>
							</div>
						</div>
                    </div>
                    <?php } ?>
                    
                    <h3 style="margin-top: 33px;">Імпорт залишків на складі з Beroo</h3>
                    <div class="form-group">
						<label class="col-sm-2 control-label" for="input-feed">Посилання на YML-експорт</label>
						<div class="col-sm-10">
							<input type="text" name="module_beroo_feed" value="<?php echo $module_beroo_feed; ?>" placeholder="Посилання на YML-експорт" id="input-feed" class="form-control" />
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-sm-2 control-label" for="input_gen">Скрипт імпорту залишків</label>
						<div class="col-sm-10">
							<input type="text" name="module_beroo_gen" value="<?php echo $module_beroo_import_stock_script; ?>" id="input_gen" class="form-control" disabled />
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-sm-2 control-label" for="input_gen">Команда Cron</label>
						<div class="col-sm-10">
							<input type="text" name="module_beroo_gen" value="curl <?php echo $module_beroo_import_stock_script; ?>" id="input_gen" class="form-control" disabled />
						</div>
                    </div>
					<div class="form-group" style="padding-left: 15px;">
						<button type="submit" form="form-beroo" data-toggle="tooltip" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp; Зберегти</button>
					</div>
               		
               		<?php if (!empty($module_beroo_domain) && !empty($module_beroo_key)) { ?>
               		<h3 style="margin-top: 33px;">Передавання статусів з Beroo на сайт</h3>
               		<div class="alert alert-info" style="margin-bottom: 0;">У Beroo налаштуйте веб-хук:
						<ul style="margin-bottom: 5px;">
               			<li>Налаштування → Загальні налаштування та інтеграції → Інші сервіси → webhook → Додати</li>
               			</ul>
               			<strong>Дані веб-хука:</strong>
						<ul>
               			<li>Подія = Зміна статусу заявки</li>
               			<li>Додайте умови:
               				<ul>
               					<li>Тип = Заявка онлайн</li>
               					<li>Сайт = Поточний сайт (потрібно, якщо у вас кілька сайтів або маркетплейсів)</li>
               				</ul>
						</li>
               			<li>URL для передавання webhook:<br>
               			<input type="text" style="width: 100%" disabled value="<?php echo $module_beroo_set_order_status; ?>">
               			</li>
               			<li>Інформація про заявку = Тільки статуси</li>
               			</ul>
               		</div>
               		<h3 style="margin-top: 33px;">Створення замовлень з Beroo на сайті</h3>
               		<div class="alert alert-info" style="margin-bottom: 0;">У Beroo налаштуйте веб-хук:
						<ul>
               			<li>Подія = Створення заявки (замовлення)</li>
               			<li>URL для передавання webhook:<br>
               			<input type="text" style="width: 100%" disabled value="<?php echo $module_beroo_create_order; ?>">
               			</li>
               			</ul>
               		</div>
					<div class="form-group">
						<label class="col-sm-2 control-label" for="input-status">Зіставлення статусів</label>
						<div class="col-sm-10">
							<?php  if (!empty($beroo_statuses_error)) { ?>
							<div class="alert alert-danger"><?php echo htmlspecialchars($beroo_statuses_error); ?></div>
							<?php } ?>
							<table class="table" style="width: auto; margin-bottom: 0;">
							<thead>
								<tr>
									<th width="250">Статус Beroo</th>
									<th width="250">Статус на сайті</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($beroo_statuses as $beroo_status){ ?>
								<tr>
									<td><?php echo htmlspecialchars($beroo_status['name']); ?></td>
									<td><select name="module_beroo_match_order_statuses[<?php echo $beroo_status['id']; ?>]" style="width:100%">
										<option>---</option>
										<?php foreach($order_statuses as $order_status){ ?>
										<option value="<?php echo $order_status['order_status_id']; ?>"
										<?php if(isset($match_order_statuses[$beroo_status['id']]) && ($match_order_statuses[$beroo_status['id']] == $order_status['order_status_id'])){ echo 'selected'; } ?>
										  ><?php echo $order_status['name']; ?></option>
										<?php } ?>
									</select></td>
								</tr>
								<?php } ?>
							<tbody>
							</table>
						</div>
					</div>
					<div class="form-group" style="padding-left: 15px;">
						<button type="submit" form="form-beroo" data-toggle="tooltip" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp; Зберегти</button>
					</div>
               		<?php } ?>
                </form>
            </div>            
        </div>
    </div>
</div>
<?php echo $footer; ?>
