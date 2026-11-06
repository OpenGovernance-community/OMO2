$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$utf8 = [Text.UTF8Encoding]::new($false, $true)

function Set-LocalEnvValues([string]$Path, [System.Collections.IDictionary]$Values) {
    $content = if ([IO.File]::Exists($Path)) { [IO.File]::ReadAllText($Path, $utf8).Replace("`r`n", "`n") } else { '' }
    foreach ($key in $Values.Keys) {
        $line = $key + '=' + $Values[$key]
        $pattern = '(?m)^' + [regex]::Escape($key) + '=.*$'
        if ([regex]::IsMatch($content, $pattern)) {
            $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $line })
        } else {
            $content = $content.TrimEnd("`n") + "`n" + $line + "`n"
        }
    }
    [IO.File]::WriteAllText($Path, $content.TrimStart("`n").TrimEnd("`n") + "`n", $utf8)
}

$jitsiEnv = Join-Path $PSScriptRoot '.env.private'
if (-not [IO.File]::Exists($jitsiEnv)) { Copy-Item -LiteralPath (Join-Path $PSScriptRoot '.env.private.example') -Destination $jitsiEnv }
$defaultRoute = Get-NetRoute -AddressFamily IPv4 -DestinationPrefix '0.0.0.0/0' | Sort-Object RouteMetric | Select-Object -First 1
$localAddress = if ($defaultRoute) { Get-NetIPAddress -InterfaceIndex $defaultRoute.InterfaceIndex -AddressFamily IPv4 | Where-Object AddressState -eq 'Preferred' | Select-Object -First 1 -ExpandProperty IPAddress }
if (-not $localAddress) { throw 'No active IPv4 network found. Connect to the network and run setup again.' }
$values = [ordered]@{ JWT_APP_ID = 'omo2'; JVB_ADVERTISE_IPS = $localAddress }
$content = [IO.File]::ReadAllText($jitsiEnv, $utf8)
foreach ($key in @('JWT_APP_SECRET', 'JICOFO_AUTH_PASSWORD', 'JVB_AUTH_PASSWORD')) {
    $match = [regex]::Match($content, '(?m)^' + $key + '=([a-f0-9]{64})\r?$')
    $values[$key] = if ($match.Success) { $match.Groups[1].Value } else { [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32)).ToLowerInvariant() }
}
Set-LocalEnvValues $jitsiEnv $values

$appEnv = Join-Path $projectRoot 'docker/app/.env.private'
if (-not [IO.File]::Exists($appEnv)) { Copy-Item -LiteralPath (Join-Path $projectRoot 'docker/app/.env.private.example') -Destination $appEnv }
Set-LocalEnvValues $appEnv ([ordered]@{
    JITSI_ENABLED = 'true'
    JITSI_URL = 'https://meet.localtest.me'
    JITSI_APP_ID = 'omo2'
    JITSI_APP_SECRET = $values['JWT_APP_SECRET']
    JITSI_XMPP_DOMAIN = 'meet.jitsi'
})
Write-Output 'Local Jitsi and OMO2 configured with matching private development keys.'
Write-Output 'Start: docker compose up -d app jitsi-web jitsi-jicofo jitsi-jvb'
