"""Load project configuration consistently without letting a legacy ML .env override it."""
from pathlib import Path
from dotenv import load_dotenv


def load_project_env(module_file: str) -> Path | None:
    module_dir = Path(module_file).resolve().parent
    project_env = next(
        (parent / ".env" for parent in module_dir.parents if (parent / "compose.yaml").is_file()),
        None,
    )
    if project_env is not None:
        load_dotenv(project_env, override=False)
    local_env = module_dir / ".env"
    if local_env.is_file():
        load_dotenv(local_env, override=False)
    return project_env
