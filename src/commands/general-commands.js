import apiFetch from "@wordpress/api-fetch";
import { dispatch } from "@wordpress/data";

const getRestBase = () => window.cloecloudCliSettings?.restUrl || "";

const execute = async (commandId, args = {}) => {
  apiFetch.use(apiFetch.createNonceMiddleware(window.cloecloudCliSettings?.nonce));

  return apiFetch({
    path: `${getRestBase()}/execute`,
    method: "POST",
    data: {
      command_id: commandId,
      args,
    },
  });
};

const fetchSettings = async () => {
  apiFetch.use(apiFetch.createNonceMiddleware(window.cloecloudCliSettings?.nonce));
  return apiFetch({ path: `${getRestBase()}/settings` });
};

export const registerGeneralCommands = () => {
  if (!window.wp?.data?.dispatch) {
    return;
  }

  fetchSettings().then((settings) => {
    const commandStore = dispatch("core/commands");
    const commands = settings?.commands || [];
    const toggles = settings?.toggles || {};

    commands.forEach((command) => {
      if (toggles[command.id] === false) {
        return;
      }

      commandStore.registerCommand({
        name: `cloecloud-cli/${command.id}`,
        label: command.label,
        callback: () => execute(command.id),
      });
    });
  });
};
