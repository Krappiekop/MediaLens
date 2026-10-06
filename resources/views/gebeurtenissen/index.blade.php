<!DOCTYPE html>
<html>

<head>
    <title>Gebeurtenissen</title>
</head>

<body>
    <h1>Gebeurtenissen</h1>
    <ul>
        @foreach ($gebeurtenissen as $gebeurtenis)
        <li>{{ $gebeurtenis->onderwerp }} ({{ $gebeurtenis->artikelen_count }} artikelen)</li>
        @endforeach
    </ul>
</body>

</html>