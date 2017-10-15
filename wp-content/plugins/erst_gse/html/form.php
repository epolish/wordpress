<h1>Настройки экспорта в Google Spreadsheet</h1>
<?php if(get_transient('erst_gse_error')): ?>
	<div id="message" class="updated notice error is-dismissible">
		<p><?= get_transient('erst_gse_error'); ?></p>
	</div>
<?php endif; ?>
<?php if(get_transient('erst_gse_success')): ?>
	<div id="message" class="updated notice notice-success is-dismissible">
		<p><?= get_transient('erst_gse_success'); ?></p>
	</div>
<?php endif; ?>
<form action="" method="post" id="erst-gse">
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="client-id">ID Клиента</label>
				</th>
				<td>
					<input name="client_id" type="text" id="client-id" aria-describedby="client-id-description"
					value="<?= esc_attr( get_option( 'erst_gse_client_id' ) ); ?>"
					class="regular-text" required>
					<p class="description" id="client-id-description">введите идентификатор клиента.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="client-secret">Секрет Клиента</label>
				</th>
				<td>
					<input name="client_secret" type="text" id="client-secret" aria-describedby="client-secret-description"
					value="<?= esc_attr( get_option( 'erst_gse_client_secret' ) ); ?>"
					class="regular-text" required>
					<p class="description" id="client-secret-description">введите секрет клиента.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="developer-key">Ключ Приложения</label>
				</th>
				<td>
					<input name="developer_key" type="text" id="developer-key" aria-describedby="developer-key-description"
					value="<?= esc_attr( get_option( 'erst_gse_developer_key' ) ); ?>"
					class="regular-text" required>
					<p class="description" id="developer-key-description">введите ключ приложения.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="spreadsheet-url">Адрес электронной таблицы (URL)</label>
				</th>
				<td>
					<input name="spreadsheet_url" type="url" id="spreadsheet-url" aria-describedby="spreadsheet-url"
					value="<?= esc_attr( get_option( 'erst_gse_spreadsheet_url' ) ); ?>"
					class="regular-text" placeholder="https://docs.google.com/spreadsheets" required>
					<p class="description" id="spreadsheet-url">введите url адрес электронной таблицы.</p>
				</td>
			</tr>
			<tr>
				<td>
					<a href="#" id="show-advanced-options">Расширенные настройки.</a>
				</td>
				<td scope="row">&nbsp;</td>
			</tr>
		</tbody>
	</table>
	<div id="advanced-options" style="display:none;margin-bottom:5px;clear:both;">
		<input type="hidden" name="table_settings">
		<div id="jsoneditor" style="width:350px;height:300px;float:right;"></div>
		<div id="info" style="width:60%;min-height:300px;">
			<h2 class="title">Инструкция по установке</h2>
			<p>
			<ol>
				<li>Зарегистрируйте свое приложениена в Google API Console
					<a href="https://console.developers.google.com" target="_blank">https://console.developers.google.com</a>
				</li>
				<li>Введите идентификатор клиента, секрет клиента, ключ приложения и адрес электронной таблицы в соответствующие поля выше</li>
				<li>Введите таблицы для экспорта в правом окне в формате
					<pre>
{
  table_name_1: {
	title: 'title_1',
	fields: {
	  field_name_1: 'name_1',
	  field_name_2: 'name_2'
	}
  },
  table_name_2: {
	title: 'title_2',
	fields: {
	  field_name_1: 'name_1',
	  field_name_2: 'name_2'
	}
  }
}
						</pre>
				</li>
				<li>Сохраните данные</li>
				<li>Обновите постоянные ссылки в
					<a href="<?= get_admin_url().'options-permalink.php'; ?>" target="_blank"> Настройках</a>
				</li>
				<li>Команда для запуска крона <kbd>*/5 * * * * curl <?= get_site_url(); ?>/erst_gse_cron/r/</kbd></li>
			</ol>
			</p>
			<br>
		</div>
	</div>
	<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="Сохранить">
	</p>
</form>
<script>
(function($) {
    $(function() {
        var editor = new JSONEditor(document.getElementById('jsoneditor'), {});
        <?php $json = get_option( 'erst_gse_table_settings' ); ?>
        editor.set(<?= $json ? $json : '{}'; ?>);
		$('#wpfooter').hide();
        $('#show-advanced-options').click(function() {
        	var advanced = $('#advanced-options');
			advanced.is(':visible') ? advanced.hide() : advanced.show();
		});
		$('#erst-gse').submit(function() {
            $("input[name='table_settings']").val(JSON.stringify(editor.get()));
        });
    });
}(jQuery));
</script>