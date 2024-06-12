<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>
</head>

<body style="padding:50px; display:flex; gap:50px; flex-wrap:wrap">
@foreach(App\Models\User::first()->images as $image)
    <x-transmorpher::dropzone :media="$image" width="300px"></x-transmorpher::dropzone>
@endforeach
@foreach(App\Models\User::first()->videos as $video)
    <x-transmorpher::dropzone :media="$video" width="300px"></x-transmorpher::dropzone>
@endforeach
</body>

</html>
