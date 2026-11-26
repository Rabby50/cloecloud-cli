import { registerGeneralCommands } from "./commands/general-commands";
import { registerUserCommands } from "./commands/user-commands";
import "./admin/style.css";
import "./admin/settings-app";

window.wp?.domReady(() => {
  registerGeneralCommands();
  registerUserCommands();
});
