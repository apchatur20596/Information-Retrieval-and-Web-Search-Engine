import java.io.File;
import java.io.FileNotFoundException;
import java.io.PrintWriter;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.regex.Pattern;
import edu.uci.ics.crawler4j.crawler.Page;
import edu.uci.ics.crawler4j.crawler.WebCrawler;
import edu.uci.ics.crawler4j.url.WebURL;

public class IRCrawler extends WebCrawler {

    public HashMap < Integer, Integer > status_Code = new HashMap();
    public HashMap < String, Integer > Content_Type = new HashMap();
    public HashMap < String, Integer > hmfetch = new HashMap();
    public HashMap < String, ArrayList > hmvisit = new HashMap();
    HashMap < String, String > hmallurl = new HashMap();
    HashMap < String, String > hmallouturl = new HashMap();

    int urlok = 0, urlnok = 0, uniqueok = 0, uniquenok = 0, unique = 0;
    int outurlok = 0, outurlnok = 0, outuniqueok = 0, outuniquenok = 0, outunique = 0,totalout=0;
    int file1kb = 0, file10kb = 0, file100kb = 0, file1mb = 0, fileg1mb = 0;

    private final static Pattern FILTERS = Pattern.compile(".*(\\.(css|js|xml|json|rss" + "|mp3|mp3|zip|gz))$");
    @Override

    public boolean shouldVisit(Page referringPage, WebURL url) {
        String href = url.getURL().toLowerCase();
        if (href.startsWith("http://www.nytimes.com/") )//|| href.startsWith("	/"))
        {
            urlok++;
            if (!(hmallurl.containsKey(href) && hmallurl.get(href).equals("OK")))
                uniqueok++;
            hmallurl.put(href, "OK");
        } else {
            urlnok++;

            if (!(hmallurl.containsKey(href) && hmallurl.get(href).equals("N_OK")))
                uniquenok++;
            hmallurl.put(href, "N_OK");

        }
        unique = uniqueok + uniquenok;
        return !FILTERS.matcher(href).matches() && (href.startsWith("http://www.nytimes.com/"));// || href.startsWith("https://www.nytimes.com/"));
    }
    @Override
    public void visit(Page page) {
        String url = page.getWebURL().getURL();
        String url2 = url.replaceAll(",", "_");
        String filesize = String.valueOf(page.getContentData().length);
        String outlinks = String.valueOf(page.getParseData().getOutgoingUrls().size());

        for(WebURL tempurl: page.getParseData().getOutgoingUrls())
        {
        	totalout++;
        	 if (tempurl.getURL().startsWith("http://www.nytimes.com/")){// || tempurl.getURL().startsWith("https://www.nytimes.com/")) {
                outurlok++;
                 if (!(hmallouturl.containsKey(tempurl.getURL()) && hmallouturl.get(tempurl.getURL()).equals("OK")))
                     outuniqueok++;
                 hmallouturl.put(tempurl.getURL(), "OK");
             } else {
                 outurlnok++;

                 if (!(hmallouturl.containsKey(tempurl.getURL()) && hmallouturl.get(tempurl.getURL()).equals("N_OK")))
                     outuniquenok++;
                 hmallouturl.put(tempurl.getURL(), "N_OK");

             }
        	
        }
        	
        String contentType = page.getContentType();
        if (contentType.startsWith("text/html")) {
            contentType = "text/html";
        }

        Integer fsize = page.getContentData().length;
        if (fsize < 1000)
            file1kb++;
        else if (fsize < 10000)
            file10kb++;
        else if (fsize < 100000)
            file100kb++;
        else if (fsize < 1000000)
            file1mb++;
        else
            fileg1mb++;

        Content_Type.put(contentType, Content_Type.containsKey(contentType) ? Content_Type.get(contentType) + 1 : 1);

        ArrayList < String > al = new ArrayList < String > ();
        al.add(filesize);
        al.add(outlinks);
        al.add(contentType);


        hmvisit.put(url2, al);

    }
    @Override
    protected void handlePageStatusCode(WebURL webUrl, int statusCode, String statusDescription) {

        String url = String.valueOf(webUrl).replaceAll(",", "_");
        status_Code.put(statusCode, status_Code.containsKey(statusCode) ? status_Code.get(statusCode) + 1 : 1);
        hmfetch.put(url, statusCode);

    }

    @Override
    public void onBeforeExit()

    {

        PrintWriter pwfetch = null;
        try {
            pwfetch = new PrintWriter(new File("fetch_NYTimes.csv"));
        } catch (FileNotFoundException e) {

            e.printStackTrace();
        }
        for (String url: hmfetch.keySet()) {
            StringBuilder sb = new StringBuilder();

            sb.append(url);
            sb.append(',');
            sb.append(hmfetch.get(url));
            sb.append('\n');

            pwfetch.write(sb.toString());
        }

        pwfetch.close();

        PrintWriter pw = null;
        try {
            pw = new PrintWriter(new File("visit_NYTimes.csv"));
        } catch (FileNotFoundException e) {

            e.printStackTrace();
        }
        for (String url: hmvisit.keySet()) {
            StringBuilder sb = new StringBuilder();

            sb.append(url);
            sb.append(',');
            sb.append(hmvisit.get(url).get(0));
            sb.append(',');
            sb.append(hmvisit.get(url).get(1));
            sb.append(',');
            sb.append(hmvisit.get(url).get(2));
            sb.append('\n');

            pw.write(sb.toString());
        }

        pw.close();

        PrintWriter pw1 = null;
        try {
            pw1 = new PrintWriter(new File("urls_NYTimes.csv"));
        } catch (FileNotFoundException e) {

            e.printStackTrace();
        }
        for (String url: hmallurl.keySet()) {
            StringBuilder sb = new StringBuilder();

            sb.append(url);
            sb.append(',');
            sb.append(hmallurl.get(url));
            sb.append('\n');

            pw1.write(sb.toString());
        }
        pw1.close();
        System.out.println(" URLs within: " + urlok + " URLs outside: " + urlnok + " unique ok: " + uniqueok + " uniquenok " + uniquenok + " unique " + unique);
        System.out.println(" URLs within: " + outurlok + " URLs outside: " + outurlnok + " unique ok: " + outuniqueok + " uniquenok " + outuniquenok + " unique total  " + totalout);


        System.out.println("Status Code: ");
        for (int url: status_Code.keySet()) {
            System.out.println(url + " : " + status_Code.get(url));
        }
        System.out.println("File sizes: " + file1kb + " " + file10kb + " " + file100kb + " " + file1mb + " " + fileg1mb);
        System.out.println("Content Type: ");
        for (String url: Content_Type.keySet()) {
            System.out.println(url + " : " + Content_Type.get(url));
        }
    }
}