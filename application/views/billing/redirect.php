<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?=__('redirecting', 'billing_transactions')?></title>
</head>
<body>
<?=__('payment_redirecting', 'billing_transactions')?><br/>
<?=$form?>
<script language="javascript" type="text/javascript">
document.payment_form.submit();
</script>
</body>
</html>