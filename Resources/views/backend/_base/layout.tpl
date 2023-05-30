<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {block name="content/header_tags"}{/block}
</head>
<body role="document">

<!-- Fixed navbar -->
<nav class="navbar navbar-inverse navbar-fixed-top">
    {block name="content/navigation"}{/block}
</nav>

<div class="container theme-showcase" role="main">
    {block name="content/main"}{/block}
</div> <!-- /container -->

<script type="text/javascript" src="{link file="backend/base/frame/postmessage-api.js"}"></script>
{block name="content/javascript"}{/block}
</body>
</html>
