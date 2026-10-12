param([string]$Php = 'php')
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path -Parent $PSScriptRoot
$taskCases = @(
    @{Mode='policy'; App='user'},
    @{Mode='templates'; App='user'},
    @{Mode='controller'; App='user'},
    @{Mode='controller'; App='glht'},
    @{Mode='controller'; App='admin'},
    @{Mode='get'; App='user'},
    @{Mode='worker'; App='open'},
    @{Mode='worker'; App='api'},
    @{Mode='worker'; App='glht'}
)
foreach ($taskCase in $taskCases) {
    & $Php (Join-Path $PSScriptRoot 'tests/export_security_test.php') $taskCase.Mode $taskCase.App
    if ($LASTEXITCODE -ne 0) { throw ('Export regression failed: ' + $taskCase.Mode + '/' + $taskCase.App) }
}
$taskPhpFiles = @(
    'shared/ExportTask.php',
    'user/Application/User/Controller/UserController.class.php',
    'glht/Application/User/Controller/UserController.class.php',
    'glht/Application/Admin/Controller/BaseController.class.php',
    'user/Application/User/Controller/DownloadController.class.php',
    'glht/Application/User/Controller/DownloadController.class.php',
    'glht/Application/Admin/Controller/DownloadController.class.php',
    'open/Application/Cli/Controller/ExportController.class.php',
    'api/Application/Cli/Controller/ExportController.class.php',
    'glht/Application/Cli/Controller/ExportController.class.php'
)
foreach ($taskPhpFile in $taskPhpFiles) {
    & $Php -l (Join-Path $taskRoot $taskPhpFile)
    if ($LASTEXITCODE -ne 0) { throw ('PHP syntax check failed: ' + $taskPhpFile) }
}
Write-Output 'All export security checks passed.'
