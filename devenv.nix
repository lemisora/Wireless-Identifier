{
  pkgs,
  lib,
  config,
  inputs,
  ...
}:

{
  # https://devenv.sh/packages/
  packages = [
    pkgs.git
  ];

  # https://devenv.sh/languages/
  languages.php = {
    enable = true;
    package = pkgs.php82;
    packages = {
      composer = pkgs.php82Packages.composer;
    };

    extensions = with pkgs.php82Extensions; [
      pdo
      mbstring
      curl
      ctype
    ];
  };

  languages.javascript = {
    enable = true;
    package = pkgs.nodejs_22;
    pnpm = {
      enable = true;
      package = pkgs.pnpm_10;
    };
    npm = {
      enable = true;
    }
  };

  services.mysql = {
    enable = true;
    initialDatabases = [
      { name = "wireless"; }
    ];
    ensureUsers = [
      {
        name = "user";
        ensurePermissions = {
          "wireless.*" = "ALL PRIVILEGES";
        };
      }
    ];
  };

  enterShell = ''
    export PATH="$PWD/vendor/bin:$PATH" # Se añade al PATH los ejecutables instalados mediante Composer para hacerlos accesibles en Devenv
    echo "Shell para desarrollo de Laravel"
  '';

  # https://devenv.sh/tests/
  enterTest = ''
    echo "Running tests"
    git --version | grep --color=auto "${pkgs.git.version}"
  '';
}
